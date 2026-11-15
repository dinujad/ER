# Phase 4.1 — අවසාන වාර්තාව (සිංහල)

**තත්වය:** PHASE 4.1 DEVELOPMENT COMPLETE — PRODUCTION VERIFICATION PENDING

## සාරාංශය

ගණකාංක Phase 4 විශේෂාංගවල ස්ථාවරත්වය සහ නිවැරදිතාව ගැටලු 11ක් සොයාගෙන නිවැරදි කරන ලදී. Phase 1–4 හැසිරීම ආරක්ෂා කෙරිණි; Phase 5 එකතු නොකෙරිණි.

## ප්‍රධාන නිවැරදි කිරීම්

1. **නියායම් ලාභ හා අලාභය (BUG-001/002):** සමාගම-wide P&L දැන් සියලු journal පේළි ඇතුළත් කරයි; cost center වාර්තාවේ `null` තවම unassigned පමණි.
2. **වසර අවසාන වසන්න (BUG-003/006):** නැවත විවෘත කිරීමට පසු නව `event_key` සහිතව නැවත වැසීම; checklist යොදා execute අවහිර කිරීම.
3. **ඉතිරිකිති පත්‍රය (BUG-004):** `current_earnings` සඳහා මෘදුකාංග වර්ෂ `starts_on` භාවිතය.
4. **බදු (BUG-008):** කාලය නැතිව adjustment post නොකෙරේ.
5. **මුදල් පුරෝකථන (BUG-009):** `amount_paid` / ගෙවීම් ඉතිරි මුදල.
6. **KPI (BUG-014):** ණය මුදල current ratio හි ඇතුළත්.
7. **ව්‍යාපාර පරාසය (BUG-012/013):** වාර්තා මකාදැමීම සහ year-end / බදු පිටු scope.

## පරීක්ෂණ

`php artisan test --filter=Accounting` → **87 passed, 251 assertions**

## ඉදිරියට

Staging මත year-end reopen සහ multi-business session පරීක්ෂණ අවශ්‍යයි. Git push **NOT PUSHED**.