@props(['minWidth' => '64rem'])

<div class="octram-report-scroll">
    <table {{ $attributes->class(['octram-report-table'])->style(["min-width: {$minWidth}"]) }}>
        {{ $slot }}
    </table>
</div>
