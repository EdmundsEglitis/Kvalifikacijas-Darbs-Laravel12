<x-filament::section>
    <x-slot name="heading">Datubāzes atjaunināšana</x-slot>

    <div class="flex items-center justify-between gap-3">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Manuāli atjaunināt NBA sadaļas datubāzi (Izmantot šo funkciju tikai, kad esiet drošs, kad šī funkcija ir vajadzīga)
        </div>


        <x-filament::button
            tag="a"
            target="_blank"
            href="{{ url('/cron/run/' . config('app.cron_token')) }}"
        >
            atjaunināt
        </x-filament::button>
    </div>
</x-filament::section>
