<?php

namespace Tigaphonic\Bazaar\User\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role;

/**
 * Static-analysis-only marker documenting the capability Bazaar requires of
 * the host app's own authenticatable model once `Spatie\Permission\Traits\
 * HasRoles` is mixed into it (a manual install-time step, same as Story 1.3
 * — AD-16: no seam for attaching a trait/interface to a foreign class other
 * than that manual step). Never `implements`-ed by anything at runtime; it
 * exists purely so `UserService` can type-hint `Model&HasRolesUser` and get
 * accurate static analysis for the trait's dynamically-mixed-in methods.
 *
 * @method syncRoles(...$roles)
 * @method BelongsToMany<Role, Model> roles()
 *
 * @property-read Collection<int, Role> $roles
 */
interface HasRolesUser {}
