# Mass Model Events for Laravel

This package is meant to provide additional model events for updating and deleting multiple model instances at a time with convenience.

This essentially adds a variable to store the models that are being updated or deleted for you to later access it in the listeners and uses a more efficient query for updating/deleting multiple models.

## How to use

### 1. Include the trait in your model

```php
<?php

use Ravenna\MassModelEvents\HasMassModelEvents;

class User extends Model
{
    use HasMassModelEvents;
}
```

### 2. Define the new model events you want to use with the observer or in the model boot method:
`massUpdating`, `massUpdated`, `massDeleting`, `massDeleted`

```php
class User extends Model
{
    use HasMassModelEvents;
    
    protected static function boot()
    {
        static::massUpdating(function (User $user) {
            $models = $user->massOperationModels();
            
            // do something with $models
        });

        parent::boot();
    }
}

```
OR
```php
class UserObserver
{
    public function massDeleting(User $user): void
    {
        $models = $user->massOperationModels();
        
        // do something with $models
    }
}
```

### 3. Link observer to the model (if using a model observer)
```php
class AppServiceProvider extends ServiceProvider
{
    ...
    public function boot(): void
    {
        User::observe(UserObserver::class);
    }
}
```
OR
```php
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(UserObserver::class)]
class User extends Model
{
    use HasMassModelEvents;
    ...
}
```

### 4. Call the methods for the events to fire

There are two methods: `patch` and `remove` that use the new model events

```php

$models = User::query()
    ->whereIn('id', $ids)
    ->get();
 
$data = [...];

User::patch($models, $data);
User::remove($models);
```

## Good to Know

1. The `patch` and `remove` methods will not fire individual model events like `updating`, `updated`, `deleting`, or `deleted` since these methods have dedicated models events for them
2. The `remove` method does not support soft deletes at the moment

## Contributions and Bug Reports

If there are any issues or bugs, please open an issue or pull request.
