<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\CustomField;
use App\Models\CustomFieldset;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PrintAssetSheetTest extends TestCase
{
    public function test_requires_permission()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('hardware.print', Asset::factory()->create()))
            ->assertForbidden();
    }

    public function test_page_renders()
    {
        $asset = Asset::factory()->create(['name' => 'SHEET-ASSET-NAME']);

        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('hardware.print', $asset))
            ->assertOk()
            ->assertSeeText($asset->asset_tag)
            ->assertSeeText('SHEET-ASSET-NAME')
            ->assertSeeText($asset->model->name)
            ->assertSeeText($asset->model->category->name);
    }

    public function test_sheet_lists_audit_and_maintenance_history()
    {
        $asset = Asset::factory()->create();
        $auditor = User::factory()->create(['first_name' => 'Audit', 'last_name' => 'Person']);

        $this->auditFor($asset, ['note' => 'AUDIT-NOTE-111', 'created_by' => $auditor->id]);

        Maintenance::factory()->create([
            'item_id' => $asset->id,
            'item_type' => Asset::class,
            'name' => 'MAINTENANCE-NAME-222',
        ]);

        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('hardware.print', $asset))
            ->assertOk()
            ->assertSeeText('AUDIT-NOTE-111')
            ->assertSeeText('Audit Person')
            ->assertSeeText('MAINTENANCE-NAME-222');
    }

    public function test_sheet_shows_audit_custom_fields_as_recorded_by_each_audit()
    {
        $this->markIncompleteIfMySQL('Custom Fields tests do not work on MySQL');

        [$asset, $field] = $this->assetWithAuditField([], 'CURRENT-CYCLE-12M');

        $this->auditFor($asset, ['log_meta' => json_encode(['_audit_snapshot' => [$field->db_column => 'PREVIOUS-CYCLE-6M']])]);

        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('hardware.print', $asset))
            ->assertOk()
            ->assertSeeText($field->name)
            ->assertSeeText('CURRENT-CYCLE-12M')
            ->assertSeeText('PREVIOUS-CYCLE-6M');
    }

    public function test_sheet_masks_encrypted_custom_fields_for_user_without_view_encrypted()
    {
        $this->markIncompleteIfMySQL('Custom Fields tests do not work on MySQL');

        [$asset, $field] = $this->assetWithAuditField(['field_encrypted' => '1'], Crypt::encrypt('CURRENT-SECRET'));

        $this->auditFor($asset, ['log_meta' => json_encode(['_audit_snapshot' => [$field->db_column => Crypt::encrypt('AUDITED-SECRET')]])]);

        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('hardware.print', $asset))
            ->assertOk()
            ->assertDontSee('CURRENT-SECRET')
            ->assertDontSee('AUDITED-SECRET')
            ->assertSeeText(strtoupper(trans('admin/custom_fields/general.encrypted')));
    }

    private function auditFor(Asset $asset, array $attributes): Actionlog
    {
        return Actionlog::factory()->for($asset, 'item')->create(array_merge([
            'action_type' => 'audit',
            'item_type' => Asset::class,
        ], $attributes));
    }

    /**
     * @return array{Asset, CustomField}
     */
    private function assetWithAuditField(array $fieldAttributes, string $value): array
    {
        $uniqueName = 'Audit Field '.uniqid();

        CustomField::factory()->create(array_merge([
            'name' => $uniqueName,
            'display_audit' => '1',
        ], $fieldAttributes));

        $field = CustomField::where('name', $uniqueName)->firstOrFail();

        $fieldset = CustomFieldset::factory()->create();
        $fieldset->fields()->attach($field, ['order' => '1', 'required' => false]);

        $model = AssetModel::factory()->create(['fieldset_id' => $fieldset->id]);

        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            $field->db_column => $value,
        ]);

        return [$asset, $field];
    }
}
