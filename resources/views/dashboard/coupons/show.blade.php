@extends('dashboard.layout.main')
@section('title', __('view') . ' - ' . $coupon->code)
@section('content')
    @include('dashboard.partials.show-page', [
        'title' => __('view') . ' - ' . $coupon->code,
        'backUrl' => route('admin.coupons.index'),
        'sections' => [
            [
                'title' => __('coupon details'),
                'rows' => [
                    ['label' => __('code'), 'value' => $coupon->code],
                    ['label' => __('discount'), 'value' => $coupon->discount . '%'],
                    [
                        'label' => __('status'),
                        'value' => $coupon->is_active
                            ? '<span class="badge badge-success">' . __('active') . '</span>'
                            : '<span class="badge badge-secondary">' . __('inactive') . '</span>',
                    ],
                ],
            ],
            [
                'title' => __('validity period'),
                'rows' => [
                    ['label' => __('start at'), 'value' => $coupon->start_at],
                    ['label' => __('end at'), 'value' => $coupon->end_at],
                    ['label' => __('created at'), 'value' => optional($coupon->created_at)->format('Y-m-d H:i')],
                    ['label' => __('updated at'), 'value' => optional($coupon->updated_at)->format('Y-m-d H:i')],
                ],
            ],
        ],
    ])
@endsection
