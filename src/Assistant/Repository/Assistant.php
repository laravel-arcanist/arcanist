<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Assistant\Repository;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $data
 * @property int $tenant_id
 * @property string $class
 * @property array $data
 */
class Assistant extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];
}
