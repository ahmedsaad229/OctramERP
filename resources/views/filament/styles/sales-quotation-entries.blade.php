<style>
    .octram-quotation-centered-entry > .fi-in-entry-label-col,
    .octram-quotation-centered-entry .fi-in-entry-label-ctn,
    .octram-quotation-centered-entry .fi-in-entry-label,
    .octram-quotation-centered-entry > .fi-in-entry-content-col,
    .octram-quotation-centered-entry .fi-in-entry-content-ctn,
    .octram-quotation-centered-entry .fi-in-entry-content {
        width: 100%;
    }

    .octram-quotation-centered-entry,
    .octram-quotation-centered-entry > .fi-in-entry-content-col,
    .octram-quotation-centered-entry .fi-in-entry-content-ctn,
    .octram-quotation-centered-entry .fi-in-entry-content {
        min-width: 0;
        overflow: visible;
    }

    .octram-quotation-centered-entry .fi-in-entry-label-ctn {
        justify-content: center;
    }

    .octram-quotation-centered-entry .fi-in-entry-label {
        direction: rtl;
        text-align: center;
    }

    .octram-quotation-readonly-box,
    .octram-quotation-summary-box {
        box-sizing: border-box;
        display: flex;
        width: 100%;
        min-width: 0;
        min-height: 2.5rem;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid rgb(209 213 219);
        border-radius: 0.5rem;
        background: rgb(249 250 251);
        padding-inline: 0.5rem;
        text-align: center;
        line-height: 1.25;
    }

    .octram-quotation-item-code-box,
    .octram-quotation-money-box,
    .octram-quotation-stock-box {
        white-space: nowrap;
    }

    .octram-quotation-item-code-box,
    .octram-quotation-money-box,
    .octram-quotation-stock-box {
        direction: ltr;
        unicode-bidi: isolate;
    }

    .octram-quotation-item-code-box {
        overflow-wrap: normal;
        font-weight: 600;
        white-space: nowrap;
        word-break: normal;
    }

    .octram-quotation-unit-box {
        overflow-wrap: normal;
        word-break: normal;
    }

    .octram-quotation-centered-field .fi-fo-field-label-ctn {
        justify-content: center;
    }

    .octram-quotation-centered-field .fi-fo-field-label {
        width: 100%;
        text-align: center;
    }

    .octram-quotation-centered-field input {
        min-height: 2.5rem;
        padding-block: 0;
        text-align: center;
    }

    @media (prefers-color-scheme: dark) {
        .octram-quotation-readonly-box,
        .octram-quotation-summary-box {
            border-color: rgb(255 255 255 / 10%);
            background: rgb(255 255 255 / 5%);
        }
    }

</style>
