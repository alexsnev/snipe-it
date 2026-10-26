<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ trans('admin/hardware/general.asset_sheet') }} - {{ $asset->asset_tag }} - {{ date('Y-m-d H:i', time()) }}</title>

    <link rel="shortcut icon" type="image/ico" href="{{ ($snipeSettings) && ($snipeSettings->favicon!='') ?  Storage::disk('public')->url(e($snipeSettings->favicon)) : config('app.url').'/favicon.ico' }}">

    {{-- stylesheets --}}
    <link rel="stylesheet" href="{{ url(mix('css/dist/all.css')) }}">

    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 10mm 12mm 10mm;
        }

        body {
            font-family: 'Frutiger LT Std', 'Segoe UI', 'Helvetica Neue', Arial, Helvetica, sans-serif;
            font-weight: 300;
            padding: 0;
            margin: 0;
            font-size: 12px;
            background: #fff;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Frutiger LT Std', Arial, Helvetica, sans-serif !important;
            font-weight: 300 !important;
        }

        h2, h3 {
            margin-top: 0;
        }

        h4 {
            margin-top: 22px;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #000;
        }

        .page-header-block {
            margin-bottom: 18px;
        }

        .print-logo {
            max-height: 50px;
            max-width: 180px;
        }

        table.sheet-summary,
        table.sheet-history {
            width: 100%;
            border-collapse: collapse;
        }

        table.sheet-summary th,
        table.sheet-summary td,
        table.sheet-history th,
        table.sheet-history td {
            border: 1px solid #d3d3d3;
            padding: 4px 6px;
            vertical-align: top;
            text-align: left;
        }

        table.sheet-summary th {
            width: 35%;
            font-weight: bold;
            background: #f5f5f5;
        }

        table.sheet-history th {
            font-weight: bold;
            background: #f5f5f5;
            font-size: 11px;
        }

        table.sheet-history td {
            font-size: 11px;
        }

        table.sheet-history td.sheet-date {
            white-space: nowrap;
        }

        @media screen {
            body {
                padding: 20px;
            }
        }

        @media print {
            tr, td, th {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            thead {
                display: table-header-group;
            }
        }
    </style>
</head>
<body>

@if ($snipeSettings->logo_print_assets=='1')
    <div class="page-header-block">
    @if ($snipeSettings->brand == '3')
        <h2>
            @if ($snipeSettings->acceptance_pdf_logo!='')
                <img class="print-logo" src="{{ Storage::disk('public')->url($snipeSettings->acceptance_pdf_logo) }}" alt="">
            @endif
            {{ $snipeSettings->site_name }}
        </h2>
    @elseif ($snipeSettings->brand == '2')
        @if ($snipeSettings->acceptance_pdf_logo!='')
            <img class="print-logo" src="{{ Storage::disk('public')->url($snipeSettings->acceptance_pdf_logo) }}" alt="">
        @endif
    @else
        <h2>{{ $snipeSettings->site_name }}</h2>
    @endif
    </div>
@endif

<h3>{{ trans('admin/hardware/general.asset_sheet') }}</h3>
<p>{{ trans('admin/users/general.all_assigned_list_generation') }} {{ Helper::getFormattedDateObject(now(), 'datetime', false) }}</p>

@php
    $lastAudit = $audits->first();
@endphp

<table class="sheet-summary">
    <tr>
        <th scope="row">{{ trans('admin/hardware/form.tag') }}</th>
        <td>{{ $asset->asset_tag }}</td>
    </tr>
    <tr>
        <th scope="row">{{ trans('admin/hardware/form.name') }}</th>
        <td>{{ $asset->name }}</td>
    </tr>
    <tr>
        <th scope="row">{{ trans('admin/hardware/form.model') }}</th>
        <td>{{ $asset->model?->name }} {{ ($asset->model?->model_number) ? '('.$asset->model->model_number.')' : '' }}</td>
    </tr>
    <tr>
        <th scope="row">{{ trans('general.category') }}</th>
        <td>{{ $asset->model?->category?->name }}</td>
    </tr>
    <tr>
        <th scope="row">{{ trans('admin/hardware/form.serial') }}</th>
        <td>{{ $asset->serial }}</td>
    </tr>
</table>

<h4>{{ trans('general.audit') }}</h4>
<table class="sheet-summary">
    <tr>
        <th scope="row">{{ trans('general.last_audit') }}</th>
        <td>
            @if ($lastAudit)
                {{ Helper::getFormattedDateObject($lastAudit->created_at, 'datetime', false) }}
                @if ($lastAudit->adminuser)
                    - {{ $lastAudit->adminuser->display_name }}
                @endif
            @elseif ($asset->last_audit_date)
                {{ Helper::getFormattedDateObject($asset->last_audit_date, 'datetime', false) }}
            @else
                {{ trans('general.na') }}
            @endif
        </td>
    </tr>
    <tr>
        <th scope="row">{{ trans('general.next_audit_date') }}</th>
        <td>{{ ($asset->next_audit_date) ? Helper::getFormattedDateObject($asset->next_audit_date, 'date', false) : trans('general.na') }}</td>
    </tr>
    @foreach ($customFields as $field)
        <tr>
            <th scope="row">{{ $field->name }}</th>
            <td><x-info-element.customfield-text :field="$field" :value="$asset->{$field->db_column_name()}"/></td>
        </tr>
    @endforeach
</table>

<h4>{{ trans('admin/hardware/general.audit_history') }}</h4>
@if ($audits->isEmpty())
    <p>{{ trans('general.no_results') }}</p>
@else
    <table class="sheet-history">
        <thead>
        <tr>
            <th scope="col">{{ trans('general.date') }}</th>
            <th scope="col">{{ trans('general.audited_by') }}</th>
            <th scope="col">{{ trans('general.location') }}</th>
            @foreach ($auditFields as $field)
                <th scope="col">{{ $field->name }}</th>
            @endforeach
            <th scope="col">{{ trans('general.notes') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($audits as $audit)
            {{-- Values of the audit custom fields as they were when this audit was recorded --}}
            @php
                $snapshot = json_decode($audit->log_meta ?? '', true)['_audit_snapshot'] ?? [];
            @endphp
            <tr>
                <td class="sheet-date">{{ Helper::getFormattedDateObject($audit->created_at, 'datetime', false) }}</td>
                <td>{{ $audit->adminuser?->display_name }}</td>
                <td>{{ $audit->location?->name }}</td>
                @foreach ($auditFields as $field)
                    <td><x-info-element.customfield-text :field="$field" :value="$snapshot[$field->db_column] ?? null"/></td>
                @endforeach
                <td>{!! nl2br(e($audit->note)) !!}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h4>{{ trans('admin/hardware/general.maintenance_history') }}</h4>
@if ($asset->maintenances->isEmpty())
    <p>{{ trans('general.no_results') }}</p>
@else
    <table class="sheet-history">
        <thead>
        <tr>
            <th scope="col">{{ trans('admin/maintenances/form.start_date') }}</th>
            <th scope="col">{{ trans('admin/maintenances/form.title') }}</th>
            <th scope="col">{{ trans('admin/maintenances/form.asset_maintenance_type') }}</th>
            <th scope="col">{{ trans('general.supplier') }}</th>
            <th scope="col">{{ trans('admin/maintenances/form.completed_at') }}</th>
            <th scope="col">{{ trans('admin/maintenances/form.notes') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($asset->maintenances as $maintenance)
            <tr>
                <td class="sheet-date">{{ Helper::getFormattedDateObject($maintenance->start_date, 'date', false) }}</td>
                <td>{{ $maintenance->name }}</td>
                <td>{{ $maintenance->maintenanceType?->name }}</td>
                <td>{{ $maintenance->supplier?->name }}</td>
                <td class="sheet-date">{{ ($maintenance->completed_at) ? Helper::getFormattedDateObject($maintenance->completed_at, 'date', false) : '' }}</td>
                <td>{!! nl2br(e($maintenance->notes)) !!}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

</body>
</html>
