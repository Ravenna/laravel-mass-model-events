<?php

namespace Ravenna\MassModelEvents\Tests;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use Ravenna\MassModelEvents\HasMassModelEvents;
use Ravenna\MassModelEvents\Tests\Models\User;

#[CoversClass(HasMassModelEvents::class)]
class EloquentMassUpdateTest extends TestCase
{
    public function test_it_can_fire_mass_updating_events(): void
    {
        Event::fake();

        $models = User::all();
        $update = $models->take(2);

        User::patch($update, ['name' => 'Updated']);

        Event::assertDispatched('eloquent.massUpdating: ' . User::class);
        Event::assertDispatched('eloquent.massUpdated: ' . User::class);

        $this->assertEquals('Updated', User::find(1)->name);
        $this->assertEquals('Updated', User::find(2)->name);
        $this->assertEquals('Dominic', User::find(3)->name);
    }

    public function test_it_can_use_observers_for_mass_updating_events(): void
    {
        $observer = new class {
            public bool $massUpdatingCalled = false;
            public bool $massUpdatedCalled = false;

            public function massUpdating(User $user): void
            {
                $this->massUpdatingCalled = true;
            }

            public function massUpdated(User $user): void
            {
                $this->massUpdatedCalled = true;
            }
        };

        $this->app->instance(get_class($observer), $observer);

        User::observe($observer);

        $models = User::all();
        $update = $models->take(2);

        User::patch($update, ['name' => 'Updated']);

        $this->assertTrue($observer->massUpdatingCalled);
        $this->assertTrue($observer->massUpdatedCalled);
        $this->assertEquals('Updated', User::find(1)->name);
        $this->assertEquals('Updated', User::find(2)->name);
    }

    public function test_it_passes_multiple_models_to_mass_updating_observer(): void
    {
        $observer = new class {
            public $updatingModels = null;
            public $updatedModels = null;

            public function massUpdating(User $user): void
            {
                $this->updatingModels = $user->getMassOperationModels();
            }

            public function massUpdated(User $user): void
            {
                $this->updatedModels = $user->getMassOperationModels();
            }
        };

        $this->app->instance(get_class($observer), $observer);

        User::observe($observer);

        $models = User::all();
        $update = $models->take(2);

        User::patch($update, ['name' => 'Updated']);

        // Verify massUpdating received the models
        $this->assertNotNull($observer->updatingModels);
        $this->assertCount(2, $observer->updatingModels);
        $this->assertInstanceOf(User::class, $observer->updatingModels->first());
        $this->assertEquals('TJ', $observer->updatingModels[0]->name);
        $this->assertEquals('James', $observer->updatingModels[1]->name);

        // Verify massUpdated received the models
        $this->assertNotNull($observer->updatedModels);
        $this->assertCount(2, $observer->updatedModels);
        $this->assertInstanceOf(User::class, $observer->updatedModels->first());
    }
}