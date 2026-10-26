@props([
    'field',
    'value' => null,
])

{{-- Plain-text rendering of a custom field value, for print views --}}
@if (($value !== null) && ($value !== ''))
    @if ($field->field_encrypted == '1')
        @can('assets.view.encrypted_custom_fields')
            {{ Helper::gracefulDecrypt($field, $value) }}
        @else
            {{ strtoupper(trans('admin/custom_fields/general.encrypted')) }}
        @endcan
    @elseif ($field->format == 'BOOLEAN')
        {{ ($value == 1) ? trans('general.yes') : trans('general.no') }}
    @elseif ($field->format == 'DATE')
        {{ Helper::getFormattedDateObject($value, 'date', false) }}
    @elseif ($field->format == 'DATETIME')
        {{ Helper::getFormattedDateObject($value, 'datetime', false) }}
    @else
        {!! nl2br(e($value)) !!}
    @endif
@endif
