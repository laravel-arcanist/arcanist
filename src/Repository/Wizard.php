<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/laravel-arcanist/arcanist
 */

namespace Arcanist\Repository;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $class
 * @property array  $data
 * @property int    $id
 */
class Wizard extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'data' => 'array',
    ];
}
