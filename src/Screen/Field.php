<?php

declare(strict_types=1);

namespace Orchid\Screen;

use Illuminate\Support\Str;
use Orchid\Screen\Traits\CanSee;
use Orchid\Screen\Contracts\FieldContract;
use Orchid\Screen\Exceptions\FieldRequiredAttributeException;

/**
 * Class Field.
 *
 * @method self accesskey($value = true)
 * @method self type($value = true)
 * @method self class($value = true)
 * @method self contenteditable($value = true)
 * @method self contextmenu($value = true)
 * @method self dir($value = true)
 * @method self hidden($value = true)
 * @method self id($value = true)
 * @method self lang($value = true)
 * @method self spellcheck($value = true)
 * @method self style($value = true)
 * @method self tabindex($value = true)
 * @method self title(string $value = null)
 * @method self options($value = true)
 * @method self autocomplete($value = true)
 */
class Field implements FieldContract
{
    use CanSee;

    /**
     * View template show.
     *
     * @var string
     */
    public $view;

    /**
     * All attributes that are available to the field.
     *
     * @var array
     */
    public $attributes = [
        'value' => null,
    ];

    /**
     * Required Attributes.
     *
     * @var array
     */
    public $required = [
        'name',
    ];

    /**
     * Vertical or Horizontal
     * bootstrap forms.
     *
     * @var string|null
     */
    public $typeForm;

    /**
     * A set of attributes for the assignment
     * of which will automatically translate them.
     *
     * @var array
     */
    public $translations = [
        'title',
        'placeholder',
        'help',
    ];

    /**
     * Universal attributes are applied to almost all tags,
     * so they are allocated to a separate group so that they do not repeat for all tags.
     *
     * @var array
     */
    public $universalAttributes = [
        'accesskey',
        'class',
        'contenteditable',
        'contextmenu',
        'dir',
        'hidden',
        'id',
        'lang',
        'spellcheck',
        'style',
        'tabindex',
        'title',
        'xml:lang',
        'autocomplete',
    ];

    /**
     * Attributes available for a particular tag.
     *
     * @var array
     */
    public $inlineAttributes = [];

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return self
     */
    public function __call(string  $name, array $arguments): self
    {
        foreach ($arguments as $key => $argument) {
            if ($argument instanceof \Closure) {
                $arguments[$key] = $argument();
            }
        }

        if (method_exists($this, $name)) {
            $this->$name($arguments);
        }

        return $this->set($name, array_shift($arguments) ?? true);
    }

    /**
     * @param mixed $value
     * @return self
     */
    public function value($value): self
    {
        $this->attributes['value'] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return self
     */
    public function set(string $key, $value = true) : self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Obtain the list of required fields.
     *
     * @return array
     */
    public function getRequired(): array
    {
        return $this->required;
    }

    /**
     * Get the name of the template.
     *
     * @return string
     */
    public function getView(): string
    {
        return $this->view;
    }

    /**
     * @return Field
     * @throws \Throwable
     */
    public function checkRequired()
    {
        foreach ($this->required as $attribute) {
            throw_if(! collect($this->attributes)->offsetExists($attribute),
                FieldRequiredAttributeException::class, $attribute);
        }

        return $this;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|mixed
     *
     * @throws \Throwable
     */
    public function render()
    {
        if (! $this->display) {
            return;
        }

        $this->checkRequired();
        $this->translate();

        $attributes = $this->getModifyAttributes();
        $this->attributes['id'] = $this->getId();

        if ($this->hasError()) {
            if (! isset($attributes['class']) || is_null($attributes['class'])) {
                $attributes['class'] = ' is-invalid';
            } else {
                $attributes['class'] .= ' is-invalid';
            }
        }

        return view($this->view, array_merge($this->getAttributes(), [
            'attributes' => $attributes,
            'id'         => $this->getId(),
            'old'        => $this->getOldValue(),
            'slug'       => $this->getSlug(),
            'oldName'    => $this->getOldName(),
            'typeForm'   => $this->typeForm ?? $this->vertical()->typeForm,
        ]));
    }

    /**
     * Localization of fields.
     *
     * @return $this
     */
    private function translate(): self
    {
        if (empty($this->translations)) {
            return $this;
        }

        $lang = $this->get('lang');

        foreach ($this->attributes as $key => $attribute) {
            if (in_array($key, $this->translations, true)) {
                $this->set($key, __($attribute, [], $lang));
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getModifyAttributes()
    {
        $modifiers = get_class_methods($this);

        collect($this->getAttributes())
            ->only(array_merge($this->universalAttributes, $this->inlineAttributes))
            ->map(function ($item, $key) use ($modifiers) {
                $key = title_case($key);
                $signature = 'modify'.$key;
                if (in_array($signature, $modifiers, true)) {
                    $this->attributes[$key] = $this->$signature($item);
                }
            });

        return collect($this->getAttributes())
            ->only(array_merge($this->universalAttributes, $this->inlineAttributes));
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        $lang = $this->get('lang');
        $slug = $this->get('name');

        return Str::slug("field-$lang-$slug");
    }

    /**
     * @param      string $key
     * @param null $value
     *
     * @return $this|mixed|null
     */
    public function get($key, $value = null)
    {
        if (! isset($this->attributes[$key])) {
            return $value;
        }

        return $this->attributes[$key];
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return Str::slug($this->get('name'));
    }

    /**
     * @return mixed
     */
    public function getOldValue()
    {
        return old($this->getOldName());
    }

    /**
     * @return string
     */
    public function getOldName(): string
    {
        $name = str_ireplace(['][', '['], '.', $this->get('name'));
        $name = str_ireplace([']'], '', $name);

        return $name;
    }

    /**
     * @return bool
     */
    private function hasError(): bool
    {
        return optional(session('errors'))->has($this->getOldName()) ?? false;
    }

    /**
     * @param mixed $name
     *
     * @return self
     */
    public function modifyName($name)
    {
        $prefix = $this->get('prefix');
        $lang = $this->get('lang');

        $this->attributes['name'] = $name;

        if (! is_null($prefix)) {
            $this->attributes['name'] = $prefix.$name;
        }

        if (is_null($prefix) && ! is_null($lang)) {
            $this->attributes['name'] = $lang.$name;
        }

        if (! is_null($prefix) && ! is_null($lang)) {
            $this->attributes['name'] = $prefix.'['.$lang.']'.$name;
        }

        if ($name instanceof \Closure) {
            $this->attributes['name'] = $name($this->attributes);
        }

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return self
     */
    public function modifyValue($value) : self
    {
        $this->attributes['value'] = $this->getOldValue() ?: $value;

        if ($value instanceof \Closure) {
            $this->attributes['value'] = $value($this->attributes);
        }

        return $this;
    }

    /**
     * @param \Closure|array $group
     *
     * @return mixed
     */
    public static function group($group)
    {
        if (! is_array($group)) {
            return $group();
        }

        return $group;
    }

    /**
     * @return self
     */
    public function vertical(): self
    {
        $this->typeForm = 'platform::partials.fields.vertical';

        return $this;
    }

    /**
     * @return self
     */
    public function horizontal(): self
    {
        $this->typeForm = 'platform::partials.fields.horizontal';

        return $this;
    }

    /**
     * @return self
     */
    public function hr(): self
    {
        $this->set('hr');

        return $this;
    }
}
