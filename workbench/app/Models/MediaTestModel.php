<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Tigaphonic\Bazaar\Media\Concerns\HasBazaarMedia;

class MediaTestModel extends Model implements HasMedia
{
    use HasBazaarMedia;

    protected $table = 'media_test_models';

    protected $guarded = [];
}
