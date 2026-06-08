<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('platform.platform_name', config('app.name', 'EventPro'));
        $this->migrator->add('platform.default_plan_id', null);
        $this->migrator->add('platform.signups_enabled', true);
        $this->migrator->add('platform.support_email', config('mail.from.address'));
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('platform.platform_name');
        $this->migrator->deleteIfExists('platform.default_plan_id');
        $this->migrator->deleteIfExists('platform.signups_enabled');
        $this->migrator->deleteIfExists('platform.support_email');
    }
};
