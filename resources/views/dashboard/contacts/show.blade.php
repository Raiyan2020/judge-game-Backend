@extends('dashboard.layout.main')
@section('title', __('view') . ' - ' . $contact->name)
@section('content')
    @include('dashboard.partials.show-page', [
        'title' => __('view') . ' - ' . $contact->name,
        'backUrl' => route('admin.contacts.index'),
        'sections' => [
            [
                'title' => __('contact information'),
                'rows' => [
                    ['label' => __('name'), 'value' => $contact->name],
                    ['label' => __('email'), 'value' => $contact->email],
                    ['label' => __('phone'), 'value' => $contact->phone],
                ],
            ],
            [
                'title' => __('message'),
                'rows' => [
                    ['label' => __('message'), 'value' => $contact->message, 'full' => true],
                    ['label' => __('created at'), 'value' => optional($contact->created_at)->format('Y-m-d H:i')],
                ],
            ],
        ],
    ])
@endsection
