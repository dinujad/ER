@if (empty($only) || in_array('sell_list_filter_location_id', $only))
    <div class="col-md-3">
        <div class="form-group">
            {!! acct_form_label('sell_list_filter_location_id', __('purchase.business_location') . ':') !!}
            {!! acct_form_select('sell_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
        </div>
    </div>
@endif
@if (empty($only) || in_array('sell_list_filter_customer_id', $only))
    <div class="col-md-3">
        <div class="form-group">
            {!! acct_form_label('sell_list_filter_customer_id', __('contact.customer') . ':') !!}
            {!! acct_form_select('sell_list_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
        </div>
    </div>
@endif
@if (empty($only) || in_array('sell_list_filter_payment_status', $only))
    <div class="col-md-3">
        <div class="form-group">
            {!! acct_form_label('sell_list_filter_payment_status', __('purchase.payment_status') . ':') !!}
            {!! acct_form_select('sell_list_filter_payment_status', ['paid' => __('lang_v1.paid'), 'due' => __('lang_v1.due'), 'partial' => __('lang_v1.partial'), 'overdue' => __('lang_v1.overdue')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
        </div>
    </div>
@endif
@if (empty($only) || in_array('sell_list_filter_date_range', $only))
    <div class="col-md-3">
        <div class="form-group">
            {!! acct_form_label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
            {!! acct_form_text('sell_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']) !!}
        </div>
    </div>
@endif
@if ((empty($only) || in_array('created_by', $only)) && !empty($sales_representative))
    <div class="col-md-3">
        <div class="form-group">
            {!! acct_form_label('created_by', __('report.user') . ':') !!}
            {!! acct_form_select('created_by', $sales_representative, null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
        </div>
    </div>
@endif
@if (empty($only) || in_array('sales_cmsn_agnt', $only))
    @if (!empty($is_cmsn_agent_enabled))
        <div class="col-md-3">
            <div class="form-group">
                {!! acct_form_label('sales_cmsn_agnt', __('lang_v1.sales_commission_agent') . ':') !!}
                {!! acct_form_select('sales_cmsn_agnt', $commission_agents, null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
            </div>
        </div>
    @endif
@endif
@if (empty($only) || in_array('service_staffs', $only))
    @if (!empty($service_staffs))
        <div class="col-md-3">
            <div class="form-group">
                {!! acct_form_label('service_staffs', __('restaurant.service_staff') . ':') !!}
                {!! acct_form_select('service_staffs', $service_staffs, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
            </div>
        </div>
    @endif
@endif
@if (!empty($shipping_statuses))
    <div class="col-md-3">
        <div class="form-group">
            {!! acct_form_label('shipping_status', __('lang_v1.shipping_status') . ':') !!}
            {!! acct_form_select('shipping_status', $shipping_statuses, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
        </div>
    </div>
@endif
@if (empty($only) || in_array('only_subscriptions', $only))
    <div class="col-md-3">
        <div class="form-group">
            <div class="checkbox">
                <label>
                    <br>
                    {!! acct_form_checkbox('only_subscriptions', 1, false, ['class' => 'input-icheck', 'id' => 'only_subscriptions']) !!} {{ __('lang_v1.subscriptions') }}
                </label>
            </div>
        </div>
    </div>
@endif
