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

use Arcanist\AbstractWizard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property class-string<AbstractWizard>|string $class
 * @property array<string, mixed>                $data
 * @property int                                 $id
 */
class Wizard extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected array $guarded = [];

    /**
     * @var array<string, string>
     */
    protected array $casts = [
        'data' => 'array',
    ];
}
