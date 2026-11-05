---
title: Custom input types
sidebar_position: 2
---

An input type is a class that extends `Javaabu\FormBuilder\InputTypes\InputType`. Register it, and forms can use it and the builder offers it.

```php
// AppServiceProvider::boot()
use Javaabu\FormBuilder\Facades\FormBuilder;

FormBuilder::registerInputType(RatingType::class);      // or add it to config('form-builder.input_types')
```

# A simple type

When a stencil field already renders what you need, the type only configures it:

```php
namespace App\FormBuilder;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\InputTypes\InputType;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\TextInput;

class NationalIdType extends InputType
{
    public static function key(): string
    {
        return 'national_id';
    }

    public function name(): string
    {
        return __('National ID card');
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        return TextInput::make($context->name($field->getKey()))
            ->maxLength(7)
            ->rules(['regex:/^A[0-9]{6}$/']);
    }
}
```

**`make()`** builds the stencil component.
- **Name it** `$context->name($field->getKey())`, so it works inside repeating groups too.
- **The compiler applies the rest after**: label, help, placeholder, required, default, width and conditions.

The other methods have defaults you override as needed:

| Method | Default | For |
| --- | --- | --- |
| `icon()`, `group()` | `text`, `text` | The builder's list. Groups: `text`, `choice`, `date`, `file`, `layout` |
| `settings()` | `[]` | Stencil components for the type's settings in the builder, named by setting key |
| `settingsRules()` | `[]` | Rules for those settings, checked on every change and import |
| `localizedSettings()` | `[]` | Settings that are text in every language: `['intro' => 'textarea']` |
| `defaults()` | `[]` | Settings a new field starts with |
| `takesInput()` | `true` | `false` for display-only types |
| `hasOptions()` | `false` | Use the `Concerns\HasOptions` trait for choice types (options editor, labels, validation) |
| `hasFields()` | `false` | Holds fields, like repeating groups |
| `isMultiple($field)` | `false` | Answers are lists |
| `canBeWatched()`, `watchesAsCheckbox()` | `takesInput()`, `false` | Whether conditions can watch it, and as a checkbox |
| `storage()` | `STORE_TEXT` | `STORE_NUMBER` or `STORE_DATE` to index answers for comparisons |
| `normalize($value, $field)` | plain values | The answer as stored |
| `format($value, $field, $locale)` | the value as text | Read-only entries, lists, `formatted()` |
| `export($value, $field)` | `format()` | CSV cells |
| `display($field, $value, $context)` | a `TextEntry` | The read-only component |

# A type with its own field

For a control javaabu/forms doesn't have, write a stencil field with a Blade view and a React component (`php artisan make:stencil-field Stars` scaffolds them; see stencil's custom fields docs), and make it from the type:

```php
class StarsField extends \Javaabu\Stencil\Fields\Field
{
    protected string $type = 'stars';

    protected ?string $view = 'stencil.stars';

    protected int|Closure $max = 5;

    public function max(int|Closure $max): static
    {
        $this->max = $max;

        return $this;
    }

    protected function getTypeRules(): array
    {
        return ['integer', 'min:1', 'max:' . $this->evaluate($this->max)];
    }

    protected function getFieldProps(): array
    {
        return ['max' => $this->evaluate($this->max)];
    }
}

class StarsType extends InputType
{
    public static function key(): string
    {
        return 'stars';
    }

    public function name(): string
    {
        return __('Stars');
    }

    public function storage(): string
    {
        return self::STORE_NUMBER;
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        return StarsField::make($context->name($field->getKey()))->max((int) $field->getSetting('max', 5));
    }

    public function format(mixed $value, Field $field, ?string $locale = null): ?string
    {
        return $value === null ? null : str_repeat('★', (int) $value);
    }

    public function settings(): array
    {
        return [TextInput::make('max')->label(__('Stars'))->integer()->minValue(2)->maxValue(10)];
    }

    public function settingsRules(): array
    {
        return ['max' => ['nullable', 'integer', 'min:2', 'max:10']];
    }
}
```

**The React component** uses @javaabu/forms' custom-field hooks, so it plugs into the form like a built-in field:
- label, help and errors
- the bound model and old input
- Inertia `useForm()` data
- `ConditionalWrapper` and the error summary

```tsx
import { splitFieldProps, useField, useFieldState, useFieldValue, usePublishField, type FieldProps } from "@javaabu/forms"
import type { StencilComponentProps } from "@javaabu/stencil"

function StarsInput(props: FieldProps & { max: number }): React.JSX.Element {
  const { field, rest } = splitFieldProps(props)
  const f = useField(field)
  const initial = String(useFieldValue(field.name, field.model, field.default) ?? "")
  const [value, setValue] = useFieldState(field.name, initial)

  usePublishField({ id: f.id, name: field.name, kind: "value", value, disabled: f.disabled, label: f.labelText })

  return f.wrap(
    <div role="radiogroup" id={f.id} aria-labelledby={`${f.baseId}-label`} aria-describedby={f.describedBy}>
      {Array.from({ length: rest.max }, (_, index) => String(index + 1)).map((star) => (
        <button key={star} type="button" role="radio" aria-checked={value === star} onClick={() => setValue(star)}>
          {"★".repeat(Number(star))}
        </button>
      ))}
      <input type="hidden" name={field.name} value={value} />
    </div>,
  )
}

/** Registered for the stencil type the PHP field renders as */
export function Stars({ node }: StencilComponentProps) {
  return <StarsInput name={node.name ?? node.key} {...(node.props as { label: string; max: number })} />
}
```

Register it next to the package's components, once for the app:

```tsx
import { StencilProvider } from "@javaabu/stencil"
import { formBuilderComponents } from "@javaabu/form-builder"

<StencilProvider components={{ ...formBuilderComponents, stars: Stars }}>
  <App />
</StencilProvider>
```

`StencilProvider` components reach every `StencilForm`, the package's Inertia pages included.

# Changing a built-in type

Change the component a type makes, for every field of that type:

```php
use Javaabu\Stencil\Fields\TextInput;

FormBuilder::extendInputType('phone', function ($component, $field, $context) {
    return $component instanceof TextInput ? $component->rules(['regex:/^[79][0-9]{6}$/']) : $component;
});
```

Replace a type with a subclass under the same key, or remove one so the builder doesn't offer it:

```php
FormBuilder::registerInputType(App\FormBuilder\TextType::class);   // extends Javaabu\FormBuilder\InputTypes\TextType
FormBuilder::forgetInputType('time');
```

Forms that already use a removed type keep their fields. The builder marks them "isn't available any more", and they can't be filled until the type is back.
