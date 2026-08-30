<?php

namespace App\Models;

use App\Enums\DocumentReviewAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentReview extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return ['action' => DocumentReviewAction::class];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewer_admin_id');
    }
}
