<style>
    :root {
        --report-primary: #173a5e;
        --report-primary-soft: #eaf2f9;
        --report-accent: #2563eb;
        --report-success: #15803d;
        --report-danger: #b91c1c;
        --report-text: #172033;
        --report-muted: #64748b;
        --report-border: #cbd5e1;
        --report-bg: #eef3f8;
        --report-white: #ffffff;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: var(--report-bg);
        color: var(--report-text);
        font-family: Arial, Tahoma, sans-serif;
        line-height: 1.55;
    }

    .statement,
    body > main,
    body > .report-page {
        width: min(210mm, calc(100% - 32px));
        min-height: 277mm;
        margin: 24px auto;
        padding: 10mm;
        background: var(--report-white);
        border-radius: 10px;
        box-shadow: 0 10px 35px rgb(15 23 42 / 12%);
    }

    .toolbar {
        display: flex;
        justify-content: flex-start;
        gap: 8px;
        margin-bottom: 14px;
    }

    .toolbar button,
    .print-button,
    button[onclick*="print"] {
        border: 0;
        border-radius: 7px;
        padding: 9px 18px;
        background: var(--report-accent);
        color: #fff;
        cursor: pointer;
        font: inherit;
        font-weight: 700;
        box-shadow: 0 3px 10px rgb(37 99 235 / 20%);
    }

    .document-header,
    body > header {
        margin-bottom: 16px;
        padding: 14px 16px;
        border: 0;
        border-bottom: 3px solid var(--report-primary);
        background: linear-gradient(180deg, #fff, #f8fbff);
    }

    .company-document-title,
    h1 {
        margin: 0;
        color: var(--report-primary);
        font-size: 23px;
        font-weight: 800;
    }

    .info,
    .totals,
    .summary {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: 10px !important;
        margin: 14px 0 !important;
    }

    .info {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    }

    .box,
    .summary > div {
        min-width: 0 !important;
        padding: 11px 12px !important;
        border: 1px solid var(--report-border) !important;
        border-radius: 8px !important;
        background: linear-gradient(180deg, #fff, #f8fafc) !important;
        box-shadow: 0 2px 7px rgb(15 23 42 / 5%);
    }

    .label,
    .meta {
        color: var(--report-muted);
        font-size: 10.5px;
    }

    .value,
    .summary strong {
        margin-top: 4px;
        color: var(--report-primary);
        font-size: 13px;
        font-weight: 800;
    }

    table {
        width: 100%;
        margin-top: 14px;
        border-collapse: separate !important;
        border-spacing: 0;
        table-layout: fixed;
        overflow: hidden;
        border: 1px solid var(--report-border);
        border-radius: 8px;
    }

    thead {
        display: table-header-group;
    }

    th,
    td {
        padding: 7px 6px !important;
        border: 0 !important;
        border-bottom: 1px solid #dbe4ee !important;
        border-left: 1px solid #e2e8f0 !important;
        vertical-align: middle;
    }

    th:last-child,
    td:last-child {
        border-left: 0 !important;
    }

    th {
        background: var(--report-primary) !important;
        color: #fff !important;
        font-weight: 800;
        text-align: center;
        white-space: nowrap;
    }

    tbody tr:nth-child(even) {
        background: #f8fafc;
    }

    tbody tr:hover {
        background: #eef6ff;
    }

    tbody tr:last-child td {
        border-bottom: 0 !important;
    }

    tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .money,
    .date,
    .reference,
    .ltr {
        direction: ltr;
        unicode-bidi: isolate;
        text-align: center;
        white-space: nowrap;
    }

    .totals:last-of-type .box:first-child {
        border-color: #86b6e6 !important;
        background: var(--report-primary-soft) !important;
    }

    .signatures {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 70px;
        margin-top: 34px;
        text-align: center;
    }

    .signatures > div {
        min-height: 65px;
        padding-top: 10px;
        border-top: 1px solid #64748b;
        font-weight: 700;
    }

    .footer,
    footer {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        margin-top: 22px;
        padding-top: 10px;
        border-top: 1px solid var(--report-border);
        color: var(--report-muted);
        font-size: 10px;
    }

    .empty {
        margin-top: 14px;
        padding: 40px 20px;
        border: 1px dashed var(--report-border);
        border-radius: 8px;
        background: #f8fafc;
        color: var(--report-muted);
        text-align: center;
    }

    @media screen and (max-width: 800px) {
        .statement,
        body > main,
        body > .report-page {
            width: 100%;
            margin: 0;
            padding: 15px;
            border-radius: 0;
            box-shadow: none;
            overflow-x: auto;
        }

        .info,
        .totals,
        .summary {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }

    @media print {
        @page {
            margin: 9mm;
        }

        body {
            background: #fff;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .toolbar {
            display: none !important;
        }

        .statement,
        body > main,
        body > .report-page {
            width: auto;
            min-height: 0;
            margin: 0;
            padding: 0;
            border-radius: 0;
            box-shadow: none;
        }

        tbody tr:hover {
            background: inherit;
        }
    }
</style>
