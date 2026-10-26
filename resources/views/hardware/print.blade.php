<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ trans('admin/hardware/general.asset_sheet') }} - {{ $asset->asset_tag }} - {{ date('Y-m-d H:i', time()) }}</title>

    <link rel="shortcut icon" type="image/ico" href="{{ ($snipeSettings) && ($snipeSettings->favicon!='') ?  Storage::disk('public')->url(e($snipeSettings->favicon)) : config('app.url').'/favicon.ico' }}">

    {{-- $cssString: page margin boxes only accept CSS strings, so drop control characters and angle brackets and escape quotes --}}
    @php
        $lastAudit = $audits->first();
        $location = $asset->location ?? $asset->defaultLoc;
        $pendingMaintenances = $asset->maintenances->whereNull('completed_at')->sortBy('start_date');
        $blankRows = 5;
        $cssString = fn ($value) => '"'.addcslashes(preg_replace('/[\x00-\x1F<>]/u', '', (string) $value), '"\\').'"';
    @endphp

    {{-- No Bootstrap stylesheet here: its print rules strip the background shading this sheet relies on --}}
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 10mm 16mm 10mm;

            @bottom-left {
                content: {!! $cssString(trans('admin/hardware/general.asset_sheet').' - '.$asset->asset_tag) !!};
                font-size: 9px;
            }

            @bottom-center {
                content: {!! $cssString(trans('admin/users/general.all_assigned_list_generation').' '.Helper::getFormattedDateObject(now(), 'datetime', false)) !!};
                font-size: 9px;
            }

            @bottom-right {
                content: counter(page) " / " counter(pages);
                font-size: 9px;
            }
        }

        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            box-sizing: border-box;
        }

        body {
            font-family: 'Frutiger LT Std', 'Segoe UI', 'Helvetica Neue', Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
            margin: 0;
        }

        .sheet-section {
            border: 1.5px solid #000;
            margin-top: -1.5px;
            padding: 6px 8px;
        }

        .sheet-section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 6px 0;
        }

        table {
            border-collapse: collapse;
        }

        table.sheet-header {
            width: 100%;
        }

        table.sheet-header td {
            padding: 4px 8px;
            vertical-align: middle;
        }

        td.sheet-header-type {
            width: 25%;
            font-size: 16px;
            font-weight: bold;
            border-right: 1.5px solid #000;
        }

        td.sheet-header-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
        }

        td.sheet-header-logo {
            width: 25%;
            text-align: right;
            border-left: 1.5px solid #000;
        }

        .print-logo {
            max-height: 45px;
            max-width: 100%;
        }

        table.sheet-columns {
            width: 100%;
        }

        table.sheet-columns > tbody > tr > td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        table.sheet-fields th {
            text-align: right;
            font-weight: bold;
            white-space: nowrap;
            vertical-align: top;
            padding: 2px 6px 2px 0;
        }

        table.sheet-fields td {
            vertical-align: top;
            padding: 2px 0;
        }

        .sheet-value-box {
            display: inline-block;
            min-width: 30mm;
            border: 1px solid #000;
            padding: 1px 4px;
        }

        .sheet-highlight {
            background: #d9d9d9;
        }

        .sheet-supplier {
            border: 1px solid #000;
            padding: 4px 6px;
            margin-left: 8px;
        }

        table.sheet-history {
            width: 100%;
        }

        table.sheet-history th,
        table.sheet-history td {
            border: 1px solid #000;
            padding: 3px 5px;
            text-align: left;
            vertical-align: top;
        }

        table.sheet-history th {
            background: #eee;
        }

        table.sheet-history tr.sheet-blank-row td {
            height: 7mm;
        }

        .sheet-date {
            white-space: nowrap;
        }

        .sheet-free-text {
            min-height: 22mm;
        }

        @media screen {
            body {
                max-width: 190mm;
                margin: 20px auto;
            }
        }

        @media print {
            tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            thead {
                display: table-header-group;
            }

            .sheet-keep-together {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

{{-- Header: sheet type, asset name, company logo --}}
<div class="sheet-section" style="padding: 0;">
    <table class="sheet-header">
        <tr>
            <td class="sheet-header-type">{{ trans('admin/hardware/general.asset_sheet') }}</td>
            <td class="sheet-header-title">{{ $asset->name ?: $asset->model?->name }}</td>
            <td class="sheet-header-logo">
                @if (($snipeSettings->logo_print_assets=='1') && ($snipeSettings->acceptance_pdf_logo!=''))
                    <img class="print-logo" src="{{ Storage::disk('public')->url($snipeSettings->acceptance_pdf_logo) }}" alt="{{ $snipeSettings->site_name }}">
                @else
                    {{ $snipeSettings->site_name }}
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- Identification --}}
<div class="sheet-section sheet-keep-together">
    <table class="sheet-columns">
        <tr>
            <td>
                <table class="sheet-fields">
                    <tr>
                        <th scope="row">{{ trans('admin/hardware/form.tag') }}:</th>
                        <td><strong>{{ $asset->asset_tag }}</strong></td>
                    </tr>
                    <tr>
                        <th scope="row">{{ trans('admin/hardware/form.name') }}:</th>
                        <td>{{ $asset->name }}</td>
                    </tr>
                    <tr>
                        <th scope="row">{{ trans('admin/hardware/form.model') }}:</th>
                        <td>{{ $asset->model?->name }} {{ ($asset->model?->model_number) ? '('.$asset->model->model_number.')' : '' }}</td>
                    </tr>
                    <tr>
                        <th scope="row">{{ trans('general.category') }}:</th>
                        <td>{{ $asset->model?->category?->name }}</td>
                    </tr>
                    <tr>
                        <th scope="row">{{ trans('general.manufacturer') }}:</th>
                        <td>{{ $asset->model?->manufacturer?->name }}</td>
                    </tr>
                    <tr>
                        <th scope="row">{{ trans('general.purchase_date') }}:</th>
                        <td>{{ ($asset->purchase_date) ? Helper::getFormattedDateObject($asset->purchase_date, 'date', false) : '' }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="sheet-fields">
                    <tr>
                        <th scope="row">{{ trans('admin/hardware/form.serial') }}:</th>
                        <td>{{ $asset->serial }}</td>
                    </tr>
                    <tr>
                        <th scope="row">{{ trans('general.location') }}:</th>
                        <td>{{ $location?->name }}</td>
                    </tr>
                    <tr>
                        <th scope="row">{{ trans('admin/hardware/form.status') }}:</th>
                        <td>{{ $asset->status?->name }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

{{-- Audit / calibration data, and supplier --}}
<div class="sheet-section sheet-keep-together">
    <table class="sheet-columns">
        <tr>
            <td>
                <table class="sheet-fields">
                    <tr>
                        <th scope="row">{{ trans('general.next_audit_date') }}:</th>
                        <td><span class="sheet-value-box sheet-highlight">{{ ($asset->next_audit_date) ? Helper::getFormattedDateObject($asset->next_audit_date, 'date', false) : trans('general.na') }}</span></td>
                    </tr>
                    <tr>
                        <th scope="row">{{ trans('general.last_audit') }}:</th>
                        <td>
                            <span class="sheet-value-box">
                                @if ($lastAudit)
                                    {{ Helper::getFormattedDateObject($lastAudit->created_at, 'date', false) }}
                                    {{ ($lastAudit->adminuser) ? '- '.$lastAudit->adminuser->display_name : '' }}
                                @elseif ($asset->last_audit_date)
                                    {{ Helper::getFormattedDateObject($asset->last_audit_date, 'date', false) }}
                                @else
                                    {{ trans('general.na') }}
                                @endif
                            </span>
                        </td>
                    </tr>
                    @foreach ($customFields as $field)
                        <tr>
                            <th scope="row">{{ $field->name }}:</th>
                            <td><span class="sheet-value-box"><x-info-element.customfield-text :field="$field" :value="$asset->{$field->db_column_name()}"/>&nbsp;</span></td>
                        </tr>
                    @endforeach
                </table>
            </td>
            <td>
                @if ($asset->supplier)
                    <div class="sheet-supplier">
                        <div class="sheet-section-title">{{ trans('general.supplier') }}</div>
                        <strong>{{ $asset->supplier->name }}</strong><br>
                        @if ($asset->supplier->address){{ $asset->supplier->address }}<br>@endif
                        @if ($asset->supplier->address2){{ $asset->supplier->address2 }}<br>@endif
                        @if ($asset->supplier->zip || $asset->supplier->city){{ $asset->supplier->zip }} {{ $asset->supplier->city }}<br>@endif
                        @if ($asset->supplier->phone)<br>{{ trans('general.phone') }}: {{ $asset->supplier->phone }}@endif
                        @if ($asset->supplier->fax)<br>{{ trans('general.fax') }}: {{ $asset->supplier->fax }}@endif
                        @if ($asset->supplier->email)<br>{{ trans('general.email') }}: {{ $asset->supplier->email }}@endif
                    </div>
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- Audit history, oldest first, with blank rows to fill in by hand --}}
<div class="sheet-section">
    <div class="sheet-section-title">{{ trans('admin/hardware/general.audit_history') }}</div>
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
        @foreach ($audits->sortBy('created_at') as $audit)
            {{-- Values of the audit custom fields as they were when this audit was recorded --}}
            @php
                $snapshot = json_decode($audit->log_meta ?? '', true)['_audit_snapshot'] ?? [];
            @endphp
            <tr>
                <td class="sheet-date">{{ Helper::getFormattedDateObject($audit->created_at, 'date', false) }}</td>
                <td>{{ $audit->adminuser?->display_name }}</td>
                <td>{{ $audit->location?->name }}</td>
                @foreach ($auditFields as $field)
                    <td><x-info-element.customfield-text :field="$field" :value="$snapshot[$field->db_column] ?? null"/></td>
                @endforeach
                <td>{!! nl2br(e($audit->note)) !!}</td>
            </tr>
        @endforeach
        @for ($i = 0; $i < $blankRows; $i++)
            <tr class="sheet-blank-row">
                @for ($column = 0; $column < $auditFields->count() + 4; $column++)
                    <td></td>
                @endfor
            </tr>
        @endfor
        </tbody>
    </table>
</div>

{{-- Maintenance history, oldest first, with blank rows to fill in by hand --}}
<div class="sheet-section">
    <div class="sheet-section-title">{{ trans('admin/hardware/general.maintenance_history') }}</div>
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
        @foreach ($asset->maintenances->sortBy('start_date') as $maintenance)
            <tr>
                <td class="sheet-date">{{ Helper::getFormattedDateObject($maintenance->start_date, 'date', false) }}</td>
                <td>{{ $maintenance->name }}</td>
                <td>{{ $maintenance->maintenanceType?->name }}</td>
                <td>{{ $maintenance->supplier?->name }}</td>
                <td class="sheet-date">{{ ($maintenance->completed_at) ? Helper::getFormattedDateObject($maintenance->completed_at, 'date', false) : '' }}</td>
                <td>{!! nl2br(e($maintenance->notes)) !!}</td>
            </tr>
        @endforeach
        @for ($i = 0; $i < $blankRows; $i++)
            <tr class="sheet-blank-row">
                <td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
        @endfor
        </tbody>
    </table>
</div>

{{-- Free-text areas, as on the paper sheet --}}
<div class="sheet-section sheet-keep-together">
    <div class="sheet-section-title">{{ trans('general.notes') }}:</div>
    <div class="sheet-free-text">{!! nl2br(e($asset->notes)) !!}</div>
</div>

<div class="sheet-section sheet-keep-together">
    <div class="sheet-section-title">{{ trans('admin/hardware/general.maintenance_to_do') }}:</div>
    <div class="sheet-free-text">
        @foreach ($pendingMaintenances as $maintenance)
            {{ Helper::getFormattedDateObject($maintenance->start_date, 'date', false) }} - {{ $maintenance->name }}{{ ($maintenance->maintenanceType) ? ' ('.$maintenance->maintenanceType->name.')' : '' }}<br>
        @endforeach
    </div>
</div>

</body>
</html>
