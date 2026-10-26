<?php

namespace App\Livewire\Concerns;

use App\Actions\Databases\CreateDatabaseSchema;
use App\Actions\Databases\DeleteDatabaseSchema;
use App\Actions\Databases\UpdateDatabaseSchema;
use App\Models\Database;
use App\Models\DatabaseInstance;

/**
 * Form kelola database schema di dalam detail instance.
 */
trait InteractsWithDatabaseSchemaForm
{
    use Notifies;

    public bool $showSchemaModal = false;

    public bool $showSchemaDeleteModal = false;

    public ?int $editingSchemaId = null;

    public ?int $deletingSchemaId = null;

    public string $schema_name = '';

    public string $schema_note = '';

    public function openSchemaCreate(): void
    {
        $this->resetSchemaForm();
        $this->showSchemaModal = true;
    }

    public function editSchema(int $id): void
    {
        $database = Database::findOrFail($id);

        $this->editingSchemaId = $database->id;
        $this->schema_name = (string) $database->name;
        $this->schema_note = (string) $database->note;

        $this->showSchemaModal = true;
    }

    public function saveSchema(): void
    {
        /** @var DatabaseInstance $instance */
        $instance = $this->instance ?? null;

        if ($instance === null) {
            return;
        }

        $data = [
            'name' => $this->schema_name,
            'note' => $this->schema_note === '' ? null : $this->schema_note,
        ];

        if ($this->editingSchemaId !== null) {
            app(UpdateDatabaseSchema::class)(Database::findOrFail($this->editingSchemaId), $data);
            $this->notify('Database '.$this->schema_name.' berhasil diperbarui.');
        } else {
            app(CreateDatabaseSchema::class)($instance, $data);
            $this->notify('Database '.$this->schema_name.' berhasil ditambahkan.');
        }

        $this->showSchemaModal = false;
        $this->resetSchemaForm();
    }

    public function askSchemaDelete(int $id): void
    {
        $this->deletingSchemaId = $id;
        $this->showSchemaDeleteModal = true;
    }

    public function deleteSchema(): void
    {
        if ($this->deletingSchemaId !== null) {
            app(DeleteDatabaseSchema::class)(Database::findOrFail($this->deletingSchemaId));
        }

        $this->notify('Database berhasil dihapus.');
        $this->showSchemaDeleteModal = false;
        $this->deletingSchemaId = null;
    }

    public function closeSchemaModal(): void
    {
        $this->showSchemaModal = false;
        $this->resetSchemaForm();
    }

    public function deletingSchema(): ?Database
    {
        return $this->deletingSchemaId !== null ? Database::find($this->deletingSchemaId) : null;
    }

    public function resetSchemaForm(): void
    {
        $this->editingSchemaId = null;
        $this->resetValidation();
        $this->schema_name = '';
        $this->schema_note = '';
    }
}
