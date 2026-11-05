---
title: AI assistants
sidebar_position: 1.25
---

The package ships guidance for AI coding assistants. This is about assistants that help *you* write code with the package. The assistant *in* the builder, which writes forms for admins, is described in [AI assistant](advanced-usage/ai-assistant.md).

- **Laravel Boost.** `php artisan boost:install` (or `boost:update`) picks up the package's guideline (`resources/boost/guidelines/core.blade.php`) and the `javaabu-form-builder` skill (`resources/boost/skills/javaabu-form-builder`). The skill covers:
  - defining forms and field types
  - conditions, repeating groups and cascading selects
  - the routes, filling and entries
  - custom input types
  - the PHP API for other packages
  - common mistakes
- **llms.txt.** `vendor/javaabu/form-builder/llms.txt` has the rules for using the package and an index of every docs page. Point an assistant at it when Boost isn't installed.
