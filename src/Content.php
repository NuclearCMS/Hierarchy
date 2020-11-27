<?php

namespace Nuclear\Hierarchy;

use Carbon\Carbon;
use Franzose\ClosureTable\Models\Entity;
use Spatie\Translatable\HasTranslations;
use Spatie\Tags\HasTags;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;
use Illuminate\Database\Eloquent\Builder;
use Bkwld\Cloner\Cloneable;

class Content extends Entity implements Searchable {

    use HasSlug, HasTags, HasTranslations, Cloneable {
        getAttributeValue as _getAttributeValue;
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contents';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * ClosureTable model instance.
     *
     * @var \Nuclear\Hierarchy\ContentClosure
     */
    protected $closure = ContentClosure::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content_type_id', 'parent_id',
        'is_visible', 'is_sterile', 'is_locked', 'status', 'hides_children', 'priority',
        'published_at', 'children_display_mode', 'title', 'slug', 'keywords',
        'meta_title', 'meta_description', 'author', 'cover_image'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'title' => 'array',
        'slug' => 'array',
        'keywords' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'author' => 'array',
        'cover_image' => 'array'
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatable = [
        'title', 'slug', 'keywords', 'meta_title',
        'meta_description', 'author', 'cover_image'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['published_at'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['cover_thumbnail'];

    /**
     * Status codes
     *
     * @var int
     */
    const DRAFT = 30;
    const PENDING = 40;
    const PUBLISHED = 50;
    const ARCHIVED = 60;

    /**
     * Schema cache
     *
     * @var null|array
     */
    protected $schema = null;

    /**
     * Cloneable relations for duplication
     *
     * @var array
     */
    protected $cloneable_relations = ['extensions', 'tags', 'children'];
    protected $clone_exempt_attributes = ['position'];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function($content)
        {
            if(empty($content->published_at)) $content->published_at = Carbon::now();
        });
    }

    /**
     * Searchable config
     *
     * @return SearchResult
     */
    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->title);
    }

    /**
     * Content Type relation
     *
     * @return BelongsTo
     */
    public function contentType()
    {
        return $this->belongsTo(ContentType::class);
    }

    /**
     * Getter for the content type
     *
     * @return ContentType
     */
    public function getContentTypeAttribute()
    {
        return $this->contentType()->first();
    }

    /**
     * Content Extension relation
     *
     * @return HasMany
     */
    public function extensions()
    {
        return $this->hasMany(ContentExtension::class);
    }

    /**
     * Returns extensions with their values
     *
     * @return array
     */
    public function formcastExtensions()
    {
        $schema = $this->getSchemaAttribute();

        foreach($schema['fields'] as $name => $type) {
            $extension = $this->getExtension($name);
            $this->setAttribute($extension->name, ($extension->type == 'MediaField' ? $extension->loadMedia()->getTranslations('value') : $extension->getTranslations('value')));
        }

        return $this;
    }

    /**
     * Returns the extension with name
     *
     * @param string $name
     * @return ContentExtension
     */
    public function getExtension($name)
    {
        if($extension = $this->extensions->firstWhere('name', $name)) return $extension;

        return $this->extensions()->save(new ContentExtension([
            'name' => $name,
            'type' => $this->schema['fields'][$name]
        ]));
    }

    /**
     * Returns the translated locales
     *
     * @return array
     */
    public function getLocalesAttribute()
    {
        return $this->getTranslatedLocales('title');
    }

    /**
     * Returns the ancestors of the content
     *
     * @return Collection
     */
    public function getAncestorsAttribute()
    {
        return array_reverse($this->ancestors()->with('contentType')->get()->toArray());
    }

    /**
    * Returns the published state of content
    *
    * @return bool
    */
    public function getIsPublishedAttribute()
    {
        return $this->status >= 50 || ($this->status == 40 && Carbon::now() <= $this->published_at);
    }

    /**
     * Returns the schema for the content's type
     *
     * @return array
     */
    public function getSchemaAttribute()
    {
        if($this->schema == null) $this->schema = get_schema_for($this->content_type_id);
        
        return $this->schema;
    }

    /**
     * Get tags attribute
     *
     * @return array
     */
    public function getTagsAttribute()
    {
        return $this->contentType->is_taggable ? $this->tags()->get() : [];
    }

    /**
     * Shortcut for extension fields
     *
     * @param string $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        if ($this->isExtensionAttribute($key)) {
            return $this->getExtension($key);
        }

        return $this->_getAttributeValue($key);
    }

    /**
     * Checks if a key is an extension attribute key
     *
     * @param string $key
     * @return bool
     */
    public function isExtensionAttribute($key)
    {
        return isset($this->schema['fields'][$key]);
    }

    /**
     * Status filter scope
     *
     * @param Builder $query
     * @param string $status
     * @return Builder
     */
    public function scopeFilteredByStatus(Builder $query, $status = null)
    {
        $status = is_null($status) ? request('f', 'all') : $status;

        if (in_array($status, ['published', 'unpublished', 'draft', 'pending', 'archived', 'invisible', 'locked']))
        {
            $query->{$status}();
        }

        return $query;
    }

    /**
     * Published scope
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePublished(Builder $query)
    {
        return $query->where(function ($query)
        {
            $query->where('status', '>=', Content::PUBLISHED)
                ->orWhere(function ($query)
                {
                    $query->where('status', '>=', Content::PENDING)
                        ->where('published_at', '<=', Carbon::now());
                });
        });
    }

    /**
     * Unpublished scope
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnpublished(Builder $query)
    {
        return $query->where(function ($query)
        {
            $query->where('status', '<=', Content::DRAFT)
                ->orWhere(function ($query)
                {
                    $query->where('status', '<=', Content::PENDING)
                        ->where('published_at', '>', Carbon::now());
                });
        });
    }

    /**
     * Draft scope
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeDraft(Builder $query)
    {
        return $query->where('status', Content::DRAFT);
    }

    /**
     * Pending scope
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePending(Builder $query)
    {
        return $query->where('status', Content::PENDING)
            ->where('published_at', '>', Carbon::now());
    }

    /**
     * Archived scope
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeArchived(Builder $query)
    {
        return $query->where('status', Content::ARCHIVED);
    }

    /**
     * Scope invisible
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInvisible(Builder $query)
    {
        return $query->where('is_visible', false);
    }

    /**
     * Scope locked
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeLocked(Builder $query)
    {
        return $query->where('is_locked', true);
    }

    /**
     * Returns the cover image thumbnail for the content
     *
     * @return string
     */
    public function getCoverThumbnailAttribute()
    {
        if($this->cover_image == null || is_null($cover = get_medium($this->cover_image))) return null;

        return $cover->imageURLFor('thumbnail');
    }

    /**
     * Modifier for duplication
     *
     * @param $source
     * @param $child
     */
    public function onCloning($source, $child)
    {
        foreach($this->getTranslations('title') as $locale => $title)
        {
            $this->setTranslation('title', $locale, $title . ' [' . __('foundation::general.copy') . ']');
        }
    }

    /**
     * Loads media for the content
     *
     * @return self
     */
    public function loadMedia()
    {
        // Load cover
        if(is_null($translations = $this->getTranslations('cover_image'))) return $this;

        foreach($translations as $locale => $translation)
        {
            if(empty($translation)) continue;

            $this->setTranslation('cover_image', $locale, is_array($translation) ? get_media($translation) : get_medium($translation));
        }

        return $this;
    }

}