<style>
    .octram-report {
        --octram-report-border: rgb(209 213 219);
        width: 100%;
        direction: rtl;
    }

    .dark .octram-report {
        --octram-report-border: rgb(255 255 255 / .14);
    }

    .octram-report-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .75rem;
    }

    .octram-report-scroll {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overscroll-behavior-inline: contain;
        padding: 1px;
    }

    .octram-report-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: auto;
        font-size: .875rem;
        line-height: 1.45;
    }

    .octram-report-table thead {
        background: rgb(249 250 251);
    }

    .octram-report-table th,
    .octram-report-table td {
        padding: .75rem 1rem;
        border: 1px solid var(--octram-report-border);
        vertical-align: middle;
        text-align: right;
    }

    .octram-report-table th {
        color: rgb(75 85 99);
        font-weight: 600;
        white-space: nowrap;
    }

    .octram-report-table tbody tr:nth-child(even) {
        background: rgb(249 250 251 / .45);
    }

    .octram-report-table tbody tr:hover {
        background: rgb(243 244 246 / .65);
    }

    .octram-report-table .octram-report-text {
        min-width: 11rem;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .octram-report-table .octram-report-text-wide {
        min-width: 16rem;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .octram-report-table .octram-report-number,
    .octram-report-table .octram-report-date,
    .octram-report-table .octram-report-code {
        white-space: nowrap;
        text-align: center;
    }

    .octram-report-table .octram-report-number,
    .octram-report-table .octram-report-date {
        direction: ltr;
        unicode-bidi: isolate;
        min-width: 8rem;
    }

    .octram-report-table .octram-report-code {
        direction: ltr;
        unicode-bidi: isolate;
        min-width: 9rem;
    }

    .dark .octram-report-table thead {
        background: rgb(255 255 255 / .06);
    }

    .dark .octram-report-table th {
        color: rgb(209 213 219);
    }

    .dark .octram-report-table tbody tr:nth-child(even) {
        background: rgb(255 255 255 / .025);
    }

    .dark .octram-report-table tbody tr:hover {
        background: rgb(255 255 255 / .05);
    }

    @media print {
        .octram-report-actions {
            display: none !important;
        }

        .octram-report-scroll {
            overflow: visible;
            padding: 0;
        }

        .octram-report-table {
            min-width: 0 !important;
        }
    }
</style>