---
title: JSON format
sidebar_position: 9
---

The export format (version 1) is what `form-builder:export` writes, what imports read, what `PUT forms/{form}/structure` takes (its `form`) and what the AI assistant answers with. `FormDefinition::toArray()` and `fromArray()` read and write `form`.

```jsonc
{
  "form_builder": 1,                         // the format version; bumps only on a breaking change
  "form": {
    "key": "job-application",                // [a-z0-9][a-z0-9_-]*, at most 100
    "name": "Job application",               // text: a string, or {"en": "…", "dv": "…"}
    "description": "…",                      // text
    "layout": "sections",                    // "sections" | "wizard"
    "settings": {
      "submit_label": "Apply",               // text
      "confirmation": "We'll call you."      // text: the thank-you message
    },
    "sections": [ /* sections */ ]
  }
}
```

Absent keys mean "not set". `locked` isn't exported: an import is never locked.

# Sections

```jsonc
{
  "key": "personal",                         // [a-z][a-z0-9_]*, unique among sections, at most 64
  "title": "Personal details",               // text
  "description": "…",                        // text
  "columns": 2,                              // 1–4
  "conditions": [ /* conditions */ ],
  "fields": [ /* fields */ ]
}
```

# Fields

```jsonc
{
  "key": "full_name",                        // [a-z][a-z0-9_]*, unique in the form (in a repeating group: in the group)
  "type": "text",                            // a registered input type
  "label": "Full name",                      // text
  "help": "…",                               // text
  "placeholder": "…",                        // text
  "required": true,
  "span": "full",                            // 1 | 2 | 3 | 4 | "full"
  "settings": { "thaana": true, "max_length": 100 },   // the type's settings (Field types)
  "conditions": [ /* conditions */ ],
  "fields": [ /* only for "repeater": its fields */ ]
}
```

Settings by type are in [Field types](../basic-usage/field-types.md). Options (`settings.options`) are `[{"value": "K", "label": "Kaafu", "parent": "…"}]`: `value` is a string and unique, `label` is text, and `parent` is for cascading selects.

Reserved keys: `_token`, `_method`, `_stencil`, `_stencil_changed`, `_stencil_old`, `_stencil_driver`.

# Conditions

```jsonc
{ "field": "applicant_type", "operator": "in", "values": ["company"] }
{ "field": "agree", "operator": "checked" }
```

| Key | What |
| --- | --- |
| `field` | The key of a field that takes an answer: a field of the same repeating group, or any field outside groups. Not the field itself |
| `operator` | `in`, `not_in`, `checked`, `not_checked` |
| `values` | Strings; required for `in` and `not_in` |

# What imports check

- the format version, and that `form` is an object
- the key patterns and lengths; that sections, fields and group fields have unique keys; no reserved keys
- that types are registered; that only repeating groups hold fields, and groups don't nest
- each type's settings rules, and options for choice types (unless they come from `options_url`)
- that text is a string or a map of locale codes to strings
- that conditions watch fields that exist and can be watched, with values where needed
- that a select's `parent` is another field with options

Errors are keyed by path (`sections.0.fields.2.settings.options.0.value`) and listed on the import page.

# Answers

An entry's `data` is keyed by field key:

| Type | Answer |
| --- | --- |
| text, textarea, email, phone, url, radio, select, time | string |
| number | int or float |
| checkbox | bool |
| checkboxes, multiple select | list of strings |
| date, datetime | `Y-m-d`, `Y-m-d H:i` |
| file | the stored path, or a list of paths |
| repeater | a list of rows, each an object of its fields' answers |

Fields hidden by their conditions have no answer.
