<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Opens every GET page of the accounting module on a schema built from the REAL migrations
 * (each migration run separately; MySQL-only ones are reported, not fatal).
 */
class AccountingFullModuleSmokeTest extends TestCase
{
    /** @var array<int, string> */
    public static array $migrationFailures = [];

    protected function setUp(): void
    {
        parent::setUp();
        self::$migrationFailures = [];
        $files = array_merge(glob(database_path('migrations/*.php')), glob(base_path('modules/Accounting/Database/Migrations/*.php')));
        usort($files, fn ($a, $b) => strcmp(basename($a), basename($b)));
        foreach ($files as $file) {
            try {
                $before = get_declared_classes();
                $m = require $file;
                if (!is_object($m)) {
                    // old-style named migration class
                    $new = array_values(array_filter(array_diff(get_declared_classes(), $before),
                        fn ($c) => is_subclass_of($c, \Illuminate\Database\Migrations\Migration::class)));
                    $m = $new ? new $new[0]() : null;
                }
                if (is_object($m) && method_exists($m, 'up')) {
                    $m->up();
                }
            } catch (\Throwable $e) {
                self::$migrationFailures[] = basename($file) . ': ' . mb_substr($e->getMessage(), 0, 160);
            }
        }
        \App\Support\SchemaCache::flush();
    }

    private function admin(): User
    {
        $u = User::query()->first();
        if (!$u) {
            $u = new User();
            $u->forceFill(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => bcrypt('x'), 'role' => 'admin']);
            $u->save();
        }
        $u->setRelation('roleModel', new Role(['name' => 'admin', 'permissions' => ['accounting.view', 'accounting.manage']]));

        return $u;
    }

    /** @var array<int, string> */
    private array $seedFailures = [];

    private function step(string $name, callable $fn): mixed
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            $t = collect($e->getTrace())->first(fn ($f) => isset($f['file']) && !str_contains($f['file'], 'vendor'));
            $this->seedFailures[] = $name . ': ' . mb_substr($e->getMessage(), 0, 250) . ($t ? ' @ ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $t['file']) . ':' . $t['line'] : '');

            return null;
        }
    }

    /** @var array<string, string> link => page it was found on */
    private array $links = [];

    /** @var array<string, string> "METHOD url" => page */
    private array $forms = [];

    /** @var array<string, array{action: string, body: string, page: string}> */
    private array $formBodies = [];

    /** Fill a form the way a user would: first option, today's date, 100, "Test". */
    private function fillForm(string $body): array
    {
        $data = [];
        $set = function (array &$data, string $name, $value) {
            if (str_ends_with($name, '[]')) {
                $data[substr($name, 0, -2)][] = $value;

                return;
            }
            parse_str(rawurlencode($name) . '=' . rawurlencode((string) $value), $part);
            $data = array_replace_recursive($data, $part);
        };
        preg_match_all('/<input\b([^>]*)>/i', $body, $inputs);
        foreach ($inputs[1] as $a) {
            if (!preg_match('/\bname="([^"]+)"/i', $a, $n) || $n[1] === '_token') {
                continue;
            }
            $type = preg_match('/\btype="(\w+)"/i', $a, $t) ? strtolower($t[1]) : 'text';
            $value = preg_match('/\bvalue="([^"]*)"/i', $a, $v) ? html_entity_decode($v[1]) : '';
            if (in_array($type, ['checkbox', 'radio'], true) && !preg_match('/\bchecked\b/i', $a) && $type === 'radio') {
                continue;
            }
            if ($type === 'file') {
                continue;
            }
            if ($value === '') {
                $value = match ($type) {
                    'date' => now()->toDateString(),
                    'month' => now()->format('Y-m'),
                    'number' => '100',
                    'email' => 'test@example.test',
                    'checkbox' => '1',
                    default => preg_match('/date/i', $n[1]) ? now()->toDateString() : 'Test',
                };
            }
            $set($data, $n[1], $value);
        }
        preg_match_all('/<select\b([^>]*)>(.*?)<\/select>/is', $body, $selects, PREG_SET_ORDER);
        foreach ($selects as [$all, $a, $opts]) {
            if (!preg_match('/\bname="([^"]+)"/i', $a, $n)) {
                continue;
            }
            preg_match_all('/<option\b[^>]*value="([^"]*)"/i', $opts, $o);
            $vals = array_values(array_filter($o[1], fn ($x) => $x !== ''));
            if ($vals) {
                $set($data, $n[1], html_entity_decode($vals[0]));
            }
        }
        preg_match_all('/<textarea\b[^>]*name="([^"]+)"[^>]*>(.*?)<\/textarea>/is', $body, $tas, PREG_SET_ORDER);
        foreach ($tas as [$all, $name, $val]) {
            $set($data, $name, trim($val) !== '' ? html_entity_decode($val) : 'Test note');
        }

        return $data;
    }

    /** @return array<int, string> */
    private function submitForms(): array
    {
        $out = [];
        $skip = '#/(logout|destroy|delete|install|uninstall|archive|password)(/|$|\?)#';
        $tried = 0;
        foreach ($this->formBodies as $key => $f) {
            [$method] = explode(' ', $key, 2);
            if ($method === 'GET' || preg_match($skip, $f['action'])) {
                continue;
            }
            $tried++;
            $data = $this->fillForm($f['body']);
            if (preg_match('/name="_method" value="(\w+)"/i', $f['body'], $mm)) {
                $method = strtoupper($mm[1]);
            }
            try {
                $res = $this->from($f['page'])->call($method === 'GET' ? 'GET' : ($method === 'POST' ? 'POST' : $method), $f['action'], $data);
                $code = $res->getStatusCode();
                if ($code >= 500) {
                    $msg = $res->exception ? (class_basename($res->exception) . ': ' . mb_substr($res->exception->getMessage(), 0, 260)) : '';
                    if ($res->exception) {
                        $t = collect($res->exception->getTrace())->first(fn ($x) => isset($x['file']) && !str_contains($x['file'], 'vendor'));
                        $msg .= $t ? ' @ ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $t['file']) . ':' . $t['line'] : '';
                    }
                    $out[] = "SUBMIT {$method} {$f['action']} → {$code} (form on {$f['page']}) {$msg}";
                }
            } catch (\Throwable $e) {
                $out[] = "SUBMIT {$method} {$f['action']} → EXC (form on {$f['page']}) " . mb_substr($e->getMessage(), 0, 220);
            }
        }
        fwrite(STDERR, "\nFORMS SUBMITTED: {$tried}\n");

        return $out;
    }

    private function collectLinks(string $page, string $html): void
    {
        $norm = function (string $u): ?string {
            $u = html_entity_decode(trim($u));
            if ($u === '' || $u[0] === '#' || str_starts_with($u, 'javascript:') || str_starts_with($u, 'mailto:') || str_starts_with($u, 'tel:')) {
                return null;
            }
            if (preg_match('#^https?://#', $u)) {
                if (!str_starts_with($u, 'http://localhost')) {
                    return null; // external (CDN, docs)
                }
                $u = substr($u, strlen('http://localhost'));
            }
            $u = preg_replace('/#.*$/', '', $u);

            return $u === '' ? null : $u;
        };
        preg_match_all('/<a\b[^>]*\bhref="([^"]*)"/i', $html, $m);
        foreach ($m[1] as $h) {
            $u = $norm($h);
            if ($u && !isset($this->links[$u])) {
                $this->links[$u] = $page;
            }
        }
        preg_match_all('/<form\b([^>]*)>(.*?)<\/form>/is', $html, $fm, PREG_SET_ORDER);
        foreach ($fm as [$all, $attrs, $body]) {
            $method = preg_match('/method="(\w+)"/i', $attrs, $mm) ? strtoupper($mm[1]) : 'GET';
            $action = preg_match('/action="([^"]*)"/i', $attrs, $am) ? $norm($am[1]) : $norm($page);
            if ($action) {
                $key = $method . ' ' . strtok($action, '?');
                $this->forms[$key] ??= $page;
                $this->formBodies[$key] ??= ['action' => $action, 'body' => $body, 'page' => $page];
            }
        }
    }

    /** @return array<int, string> */
    private function checkLinks(): array
    {
        $out = [];
        $skip = '#/(logout|destroy|delete|install|uninstall|reverse|clear-all)(/|$|\?)#';
        foreach ($this->links as $link => $page) {
            if (preg_match($skip, $link) || str_starts_with($link, '/storage/') || preg_match('#\.(css|js|png|jpg|svg|ico)(\?|$)#', $link)) {
                continue;
            }
            try {
                $res = $this->get($link);
                $code = $res->getStatusCode();
                if ($code >= 500 || $code === 404 || $code === 405) {
                    $msg = $res->exception ? (class_basename($res->exception) . ': ' . mb_substr($res->exception->getMessage(), 0, 220)) : '';
                    $out[] = "LINK {$link} → {$code} (on {$page}) {$msg}";
                }
            } catch (\Throwable $e) {
                $out[] = "LINK {$link} → EXC (on {$page}) " . mb_substr($e->getMessage(), 0, 200);
            }
        }
        foreach ($this->forms as $key => $page) {
            [$method, $action] = explode(' ', $key, 2);
            $method = in_array($method, ['GET', 'POST'], true) ? $method : 'POST';
            try {
                Route::getRoutes()->match(\Illuminate\Http\Request::create($action, $method));
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                $out[] = "FORM {$method} {$action} → no such route (on {$page})";
            } catch (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                $out[] = "FORM {$method} {$action} → wrong method (on {$page})";
            }
        }
        fwrite(STDERR, "\nLINKS CHECKED: " . count($this->links) . " · FORMS CHECKED: " . count($this->forms) . "\n");

        return $out;
    }

    /** Insert only the columns the real table has. */
    private function ins(string $table, array $row): int
    {
        $cols = array_flip(Schema::getColumnListing($table));
        $row = array_intersect_key($row + ['created_at' => now(), 'updated_at' => now()], $cols);
        // fill other required columns (NOT NULL, no default) with harmless values
        foreach (DB::select("PRAGMA table_info('{$table}')") as $c) {
            if ($c->notnull && $c->dflt_value === null && !$c->pk && !array_key_exists($c->name, $row)) {
                $t = strtolower((string) $c->type);
                if (str_ends_with($c->name, '_id')) {
                    $ref = rtrim(substr($c->name, 0, -3), 's') . 's';
                    $row[$c->name] = Schema::hasTable($ref) ? (DB::table($ref)->value('id') ?? $this->ins($ref, ['name' => 'Auto ' . $ref])) : 1;
                } else {
                    $row[$c->name] = str_contains($t, 'int') || str_contains($t, 'num') || str_contains($t, 'dec') || str_contains($t, 'real') ? 0
                        : (str_contains($t, 'date') || str_contains($t, 'time') ? now() : 'auto-' . $c->name);
                }
            }
        }

        return (int) DB::table($table)->insertGetId($row);
    }

    private function seedData(int $userId): void
    {
        $this->step('coa seeder', fn () => $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class));
        $gl = fn ($c) => \App\Services\AccountingService::accountId($c);
        $today = now()->startOfMonth()->addDays(3)->toDateString();

        $order = $this->step('sales invoice', function () use ($userId, $today) {
            $cust = \App\Models\Customer::query()->create(['name' => 'Smoke Customer', 'business_name' => 'Smoke Stores', 'phone' => '0771234567', 'tin' => '123456789', 'customer_type' => 'wholesale', 'credit_limit' => 5000]);
            $cat = $this->ins('categories', ['name' => 'Trousers', 'slug' => 'trousers']);
            $pid = $this->ins('products', ['name' => 'Trouser', 'sku' => 'TR-1', 'price' => 1000, 'type' => 'single', 'status' => 'active', 'category_id' => $cat]);
            $o = \App\Models\Order::query()->create(['order_number' => 'ORD-SM1', 'customer_id' => $cust->id, 'user_id' => $userId, 'subtotal' => 10000,
                'discount_amount' => 0, 'shipping' => 0, 'invoice_other_expenses' => 0, 'total_amount' => 10000, 'status' => 'pending', 'order_date' => $today]);
            \App\Models\OrderItem::query()->create(['order_id' => $o->id, 'product_id' => $pid, 'quantity' => 10, 'unit_price' => 1000, 'total_price' => 10000]);
            $o->refresh()->load('customer');
            $o->invoice_number = \App\Models\Order::nextInvoiceNumber();
            $o->status = \App\Models\Order::STATUS_COMPLETED;
            $o->applyInvoiceTax();
            $o->stampInvoiceDates(now()->subDays(40));
            $o->save();
            app(\App\Services\SalesAccountingService::class)->synchronizeOrder($o->fresh());

            return $o->fresh();
        });
        if ($order) {
            $this->step('customer payment', function () use ($order, $today) {
                $p = \App\Models\Payment::query()->create(['order_id' => $order->id, 'amount' => 2000, 'payment_method' => 'bank_transfer', 'payment_date' => $today, 'reference_number' => 'R1']);
                app(\App\Services\SalesAccountingService::class)->synchronizePayment($p->fresh());
            });
            $this->step('cheque payment', fn () => \App\Models\Payment::query()->create(['order_id' => $order->id, 'amount' => 1000, 'payment_method' => 'cheque', 'payment_date' => $today, 'cheque_number' => '000123', 'cheque_date' => $today, 'cheque_status' => 'pending']));
            $this->step('sale return', fn () => \App\Models\SaleReturn::query()->create(['return_no' => 'SR-SM1', 'order_id' => $order->id, 'customer_id' => $order->customer_id,
                'return_date' => $today, 'status' => 'confirmed', 'subtotal' => 500, 'total' => 500, 'channel' => 'wholesale', 'created_by' => $userId]));
            $this->step('credit note', fn () => app(\App\Services\Accounting\ReceivablesService::class)->issueAdjustmentCreditNote($order->fresh(), 300, 'Goodwill'));
        }

        $this->step('bill + payment', function () use ($userId, $today) {
            $sup = \App\Models\Supplier::query()->create(['code' => 'SUP-SM', 'name' => 'Smoke Mills', 'company_name' => 'Smoke Mills', 'tax_vat_no' => '114455667', 'status' => 'active', 'phone' => '0112345678',
                'bank_account_no' => '0012345678', 'bank_name' => 'BOC', 'bank_code' => '7010', 'branch_code' => '001']);
            $p = \App\Models\Purchase::query()->create(['purchase_no' => 'PUR-SM1', 'date' => now()->subDays(35)->toDateString(), 'supplier_id' => $sup->id, 'purchase_type' => 'credit',
                'status' => 'confirmed', 'subtotal' => 20000, 'discount_amount' => 0, 'tax_amount' => 3600, 'shipping_cost' => 0, 'total' => 23600, 'paid_amount' => 0,
                'input_vat_claimable' => true, 'reference_no' => 'SM-77', 'created_by' => $userId, 'confirmed_at' => now()]);
            app(\App\Observers\PurchaseObserver::class)->backfill($p->fresh());
            $pp = \App\Models\PurchasePayment::query()->create(['supplier_id' => $sup->id, 'purchase_id' => $p->id, 'entry_type' => 'payment', 'amount' => 3600, 'payment_method' => 'bank_transfer', 'payment_date' => $today, 'created_by' => $userId]);
            app(\App\Observers\PurchasePaymentObserver::class)->sync((int) $pp->id);
            $run = app(\App\Services\Accounting\PaymentRunService::class)->create([$p->id], $today, null, 'smoke', $userId);
            app(\App\Services\Accounting\PaymentRunService::class)->approve($run->id, $userId);
        });

        $this->step('manual journal', function () use ($gl, $today, $userId) {
            $j = \App\Services\Accounting\ManualJournalService::createDraft(['journal_date' => $today, 'description' => 'Rent'], [
                ['account_id' => $gl('6000'), 'debit' => 2500, 'credit' => 0],
                ['account_id' => $gl('1000'), 'debit' => 0, 'credit' => 2500],
            ], $userId);
            \App\Services\Accounting\ManualJournalService::post($j, false, $userId);
        });

        $this->step('bank account + statement + reconcile', function () use ($gl, $today, $userId) {
            $acc = \App\Services\Accounting\BankAccountService::create(['bank_name' => 'Commercial Bank', 'display_name' => 'ComBank Main', 'account_type' => 'current',
                'account_number' => '8001234566789', 'ledger_mode' => 'existing', 'chart_of_account_id' => $gl('1010')], $userId);
            $imp = \App\Models\Accounting\BankStatementImport::query()->create(['chart_of_account_id' => $gl('1010'), 'status' => 'imported', 'row_count' => 2, 'original_filename' => 'nov.csv']);
            $imp->forceFill(['closing_balance' => 1950, 'statement_to' => $today])->save();
            \App\Models\Accounting\BankStatementLine::query()->create(['bank_statement_import_id' => $imp->id, 'txn_date' => $today, 'description' => 'CEFT SMOKE', 'credit' => 2000, 'debit' => 0, 'amount' => 2000, 'fingerprint' => 'f1', 'match_status' => 'unmatched']);
            \App\Models\Accounting\BankStatementLine::query()->create(['bank_statement_import_id' => $imp->id, 'txn_date' => $today, 'description' => 'SMS FEE', 'credit' => 0, 'debit' => 50, 'amount' => -50, 'fingerprint' => 'f2', 'match_status' => 'unmatched']);
            $ids = DB::table('journal_entries')->where('chart_of_account_id', $gl('1010'))->where('reversed', 0)->pluck('id')->all();
            $bal = \App\Services\Accounting\BankAccountService::bookBalance(\App\Services\Accounting\BankAccountService::find($acc->id), $today);
            \App\Services\Accounting\BankAccountService::finishReconciliation(\App\Services\Accounting\BankAccountService::find($acc->id), $today, $bal, $ids, 'smoke', $userId);
        });

        $this->step('fixed asset', function () {
            $cat = $this->ins('fa_asset_categories', ['business_id' => 0, 'name' => 'Machinery', 'code' => 'MACH', 'default_useful_life_months' => 60, 'depreciation_method' => 'straight_line', 'active' => 1]);
            \App\Services\Accounting\FixedAssetService::createDraft(['category_id' => $cat, 'name' => 'Sewing machine', 'asset_cost' => 150000, 'useful_life_months' => 60]);
        });
        $this->step('cost center', fn () => $this->ins('acct_cost_centers', ['business_id' => 0, 'code' => 'CC1', 'name' => 'Cutting', 'active' => 1, 'status' => 'active']));
        $this->step('expense', function () use ($userId, $today) {
            $cat = $this->ins('expense_categories', ['name' => 'Electricity', 'code' => 'ELEC']);
            \App\Models\Expense::query()->create(['expense_category_id' => $cat, 'amount' => 4500, 'payment_method' => 'bank', 'expense_date' => $today, 'reference' => 'CEB-11', 'created_by' => $userId]);
        });
    }

    public function test_every_accounting_get_page(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $this->seedData((int) $admin->id);
        $id = fn (string $table) => Schema::hasTable($table) ? (DB::table($table)->value('id') ?? 1) : 1;

        $params = [
            'accountId' => \App\Services\AccountingService::accountId('1010') ?? $id('chart_of_accounts'), 'id' => 1, 'recId' => $id('bank_reconciliations'),
            'assetId' => $id('fa_assets'), 'attachmentId' => 1,
            'order' => $id('orders'), 'purchase' => $id('purchases'), 'transactionNumber' => DB::table('journal_entries')->value('transaction_number') ?? 'TXN-NONE',
            'type' => 'invoice', 'section' => 'sales', 'statement' => 'profit-and-loss', 'format' => 'csv',
        ];
        // {id} means different tables on different pages
        $idFor = [
            'accounting/banking/' => $id('bank_accounts'), 'accounting/phase7/payment-runs/' => $id('acct_payment_runs'),
            'accounting/fixed-assets/' => $id('fa_assets'), 'accounting/manual-journals/' => $id('accounting_manual_journals'),
            'accounting/bank-statements/' => $id('bank_statement_imports'), 'accounting/phase5/ocr/' => $id('acct_ocr_documents'),
            'accounting/cost-centers/' => $id('acct_cost_centers'), 'accounting/chart_of_account/' => \App\Services\AccountingService::accountId('1010') ?? 1,
            'accounting/journal_entry/' => DB::table('journal_entries')->value('transaction_number') ?? 1,
            'accounting/phase6/credit-notes/' => $id('credit_notes'), 'accounting/phase6/audit-trail/document/' => $id('orders'),
        ];
        $skip = ['accounting/install', 'accounting/install/uninstall', 'accounting/install/update', 'accounting/chart_of_account/{id}/destroy',
            'accounting/journal_entry/{id}/destroy', 'accounting/journal_entry/{id}/reverse'];

        $failures = [];
        foreach (Route::getRoutes() as $r) {
            $uri = $r->uri();
            if (!in_array('GET', $r->methods(), true) || !(str_starts_with($uri, 'accounting') || str_starts_with($uri, 'report/accounting')) || in_array($uri, $skip, true)) {
                continue;
            }
            $p = $params;
            foreach ($idFor as $prefix => $v) {
                if (str_starts_with($uri, $prefix)) {
                    $p['id'] = $v;
                }
            }
            $url = '/' . preg_replace_callback('/\{(\w+)\??\}/', fn ($m) => (string) ($p[$m[1]] ?? 1), $uri);
            try {
                $res = $this->get($url);
                $code = $res->getStatusCode();
                if ($code !== 200) {
                    $GLOBALS['__non200'][] = "{$url} → {$code}" . ($res->isRedirect() ? ' → ' . $res->headers->get('Location') : '');
                }
                if ($code === 200 && str_contains((string) $res->headers->get('Content-Type'), 'html')) {
                    $this->collectLinks($url, (string) $res->getContent());
                }
                if ($code >= 500) {
                    $msg = $res->exception ? (class_basename($res->exception) . ': ' . $res->exception->getMessage()) : '';
                    if ($res->exception) {
                        $t = collect($res->exception->getTrace())->first(fn ($f) => isset($f['file']) && !str_contains($f['file'], 'vendor'));
                        $msg .= $t ? ' @ ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $t['file']) . ':' . $t['line'] : '';
                    }
                    $failures[] = "{$url} → {$code} " . mb_substr($msg, 0, 400);
                }
            } catch (\Throwable $e) {
                $failures[] = "{$url} → EXC " . mb_substr($e->getMessage(), 0, 300);
            }
        }

        // Every link and form found on those pages
        foreach ($this->checkLinks() as $f) {
            $failures[] = $f;
        }
        foreach ($this->submitForms() as $f) {
            $failures[] = $f;
        }

        // Exports / PDFs with proper parameters
        $from = now()->startOfYear()->toDateString();
        $to = now()->toDateString();
        $cust = DB::table('customers')->value('id');
        $sup = DB::table('suppliers')->value('id');
        $bankGl = \App\Services\AccountingService::accountId('1010');
        $exports = [
            '/accounting/chart_of_account/export?type=csv', '/accounting/chart_of_account/export?type=excel', '/accounting/chart_of_account/export?type=pdf',
            "/accounting/account-register/export?account_id={$bankGl}&from={$from}&to={$to}",
            "/accounting/receivables/customer-statement/pdf?customer_id={$cust}&from={$from}&to={$to}",
            "/accounting/payables/supplier-statement/pdf?supplier_id={$sup}&from={$from}&to={$to}",
            "/accounting/bank-reconciliation-report/pdf?chart_of_account_id={$bankGl}&account_id={$bankGl}&from={$from}&to={$to}&statement_balance=0",
            '/accounting/fixed-assets/reports/register/pdf', '/accounting/fixed-assets/reports/register/xlsx', '/accounting/fixed-assets/reports/register/csv',
            '/accounting/fixed-assets/reports/disposals/pdf', "/accounting/fixed-assets/{$params['assetId']}/reports/depreciation/pdf", "/accounting/fixed-assets/{$params['assetId']}/reports/history/pdf",
            "/accounting/cost-center-reports/profit-and-loss/pdf?from={$from}&to={$to}", "/accounting/cost-center-reports/profit-and-loss/xlsx?from={$from}&to={$to}",
            "/accounting/phase6/credit-notes/{$idFor['accounting/phase6/credit-notes/']}/pdf",
            "/accounting/phase6/vat-return?from={$from}&to={$to}&export=csv", "/accounting/phase6/audit-trail?export=csv",
            "/accounting/phase7/payment-runs/{$idFor['accounting/phase7/payment-runs/']}/export?format=slips_csv",
        ];
        foreach (['profit-and-loss', 'balance-sheet', 'trial-balance', 'cash-flow', 'changes-in-equity'] as $st) {
            foreach (['pdf', 'xlsx', 'csv'] as $fmt) {
                $exports[] = "/accounting/phase4/statements/{$st}/{$fmt}?from={$from}&to={$to}&as_of={$to}";
            }
        }
        foreach (['trial_balance', 'balance_sheet', 'profit_and_loss', 'cash_flow', 'ledger', 'journal', 'accounts_receivable_ageing_summary', 'accounts_payable_ageing_summary'] as $rep) {
            $exports[] = "/report/accounting/{$rep}?start_date={$from}&end_date={$to}&format=pdf";
        }
        foreach ($exports as $url) {
            try {
                $res = $this->get($url);
                $code = $res->getStatusCode();
                if ($code >= 400) {
                    $msg = $res->exception ? (class_basename($res->exception) . ': ' . $res->exception->getMessage()) : '';
                    if ($res->exception) {
                        $t = collect($res->exception->getTrace())->first(fn ($f) => isset($f['file']) && !str_contains($f['file'], 'vendor'));
                        $msg .= $t ? ' @ ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $t['file']) . ':' . $t['line'] : '';
                    }
                    $failures[] = "EXPORT {$url} → {$code} " . mb_substr($msg, 0, 300);
                } elseif ($code === 302) {
                    $GLOBALS['__non200'][] = "EXPORT {$url} → 302 " . (session('error') ?? '');
                }
            } catch (\Throwable $e) {
                $failures[] = "EXPORT {$url} → EXC " . mb_substr($e->getMessage(), 0, 300);
            }
        }

        fwrite(STDERR, "\nNON-200 (" . count($GLOBALS['__non200'] ?? []) . "):\n" . implode("\n", $GLOBALS['__non200'] ?? []) . "\n");
        fwrite(STDERR, "\nSEED FAILURES (" . count($this->seedFailures) . "):\n" . implode("\n", $this->seedFailures) . "\n");
        fwrite(STDERR, "\nMIGRATION FAILURES (" . count(self::$migrationFailures) . "):\n" . implode("\n", self::$migrationFailures) . "\n");
        fwrite(STDERR, "\nPAGE FAILURES (" . count($failures) . "):\n" . implode("\n", $failures) . "\n");
        $this->assertSame([], $failures);
    }
}
