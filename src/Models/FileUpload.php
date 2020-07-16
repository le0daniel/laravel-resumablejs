<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 01.06.19
 * Time: 15:39
 */

namespace le0daniel\LaravelResumableJs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Class FileUpload
 * @package le0daniel\LaravelResumableJs\Models
 *
 * @property integer $size
 * @property integer $chunks
 * @property string $name
 * @property string $extension
 * @property string $type
 * @property string $token
 * @property string $handler
 * @property array $payload
 * @property boolean $is_complete
 */
class FileUpload extends Model
{
    protected $table = 'fileuploads';
    protected $fillable = ['size', 'chunks', 'name', 'extension', 'type', 'payload'];
    protected $casts = [
        'payload' => 'array',
        'is_complete' => 'boolean',
    ];

    public function appendToPayload(string $key, $value): void
    {
        $payload = $this->payload;
        Arr::set($payload, $key, $value);
        $this->payload = $payload;
    }

}
