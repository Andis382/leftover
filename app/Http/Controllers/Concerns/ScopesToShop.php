<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Another bakery's row does not exist.
 *
 * Deliberately 404 and not 403: "you may not see this" confirms that a row with
 * that id is there, which is a small leak and an entirely avoidable one. A
 * bakery's product list and its waste figures are commercially sensitive in a
 * street with three bakeries on it.
 */
trait ScopesToShop
{
    protected function mine(?Model $model): Model
    {
        if (! $model || (int) $model->user_id !== (int) auth()->id()) {
            throw new NotFoundHttpException();
        }

        return $model;
    }
}
