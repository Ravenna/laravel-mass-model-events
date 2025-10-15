<?php

namespace Ravenna\MassModelEvents\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use Ravenna\MassModelEvents\HasMassModelEvents;
use Ravenna\MassModelEvents\Tests\Models\User;

#[CoversClass(HasMassModelEvents::class)]
class EloquentMassDeleteTest extends TestCase
{
    public function test_it_can_fire_mass_deleting_events(): void
    {
        Event::fake();

        $models = User::all();
        $delete = $models->take(2);

        User::remove($delete);

        Event::assertDispatched('eloquent.massDeleting: ' . User::class);
        Event::assertDispatched('eloquent.massDeleted: ' . User::class);

        $this->assertCount(1, User::all());
    }

    public function test_it_can_use_observers_for_mass_deleting_events(): void
    {
        $observer = new class {
            public bool $massDeletingCalled = false;
            public bool $massDeletedCalled = false;

            public function massDeleting(User $user): void
            {
                $this->massDeletingCalled = true;
            }

            public function massDeleted(User $user): void
            {
                $this->massDeletedCalled = true;
            }
        };

        $this->app->instance(get_class($observer), $observer);

        User::observe($observer);

        $models = User::all();
        $delete = $models->take(2);

        User::remove($delete);

        $this->assertCount(1, User::all());
        $this->assertTrue($observer->massDeletingCalled);
        $this->assertTrue($observer->massDeletedCalled);
    }

    public function test_it_passes_multiple_models_to_mass_deleting_observer(): void
    {
        $observer = new class {
            public $deletingModels = null;
            public $deletedModels = null;

            public function massDeleting(User $user): void
            {
                $this->deletingModels = $user->getMassOperationModels();
            }

            public function massDeleted(User $user): void
            {
                $this->deletedModels = $user->getMassOperationModels();
            }
        };

        $this->app->instance(get_class($observer), $observer);

        User::observe($observer);

        $models = User::all();
        $delete = $models->take(2);

        User::remove($delete);

        // Verify massDeleting received the models
        $this->assertNotNull($observer->deletingModels);
        $this->assertCount(2, $observer->deletingModels);
        $this->assertInstanceOf(User::class, $observer->deletingModels->first());
        $this->assertEquals('TJ', $observer->deletingModels[0]->name);
        $this->assertEquals('James', $observer->deletingModels[1]->name);

        // Verify massDeleted received the models
        $this->assertNotNull($observer->deletedModels);
        $this->assertCount(2, $observer->deletedModels);
        $this->assertInstanceOf(User::class, $observer->deletedModels->first());
    }
}