<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    /** Use the event's title (from the JSON payload) as the page heading. */
    public function getTitle(): string
    {
        /** @var Event $record */
        $record = $this->getRecord();

        return $record->title();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /** Expose the UTC start as an editable datetime. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! empty($data['created_time'])) {
            $data['starts_at'] = CarbonImmutable::createFromTimestampUTC((int) $data['created_time'])->format('Y-m-d H:i:s');
        }

        return $data;
    }

    /** Merge edited payload keys into the existing payload and sync the start. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['payload'] = array_replace_recursive($this->record->payload ?? [], $data['payload'] ?? []);

        if (! empty($data['starts_at'])) {
            $ts = CarbonImmutable::parse($data['starts_at'], 'UTC')->timestamp;
            $data['created_time'] = $ts;
            $data['payload']['schedule']['starts_at'] = $ts;
        }

        unset($data['starts_at']);

        return $data;
    }
}
