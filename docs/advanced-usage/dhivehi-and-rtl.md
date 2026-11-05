---
title: Dhivehi and RTL
sidebar_position: 8
---

# Strings

Every string the package shows goes through `__()` with its English text as the key:
- page text, buttons, messages
- builder labels, field type names
- validation messages

English and Dhivehi lines are in `lang/en.json` and `lang/dv.json`. The Blade and React pages get the same strings from the server (`strings` in the props), so both stacks read the same.

Publish the files to change lines:

```bash
php artisan vendor:publish --tag=form-builder-translations    # lang/vendor/form-builder/{en,dv}.json
```

# Thaana and RTL

- Mark free-text fields that take Dhivehi with `thaana()` (in the builder: "Dhivehi (Thaana) keyboard"). javaabu/forms' Thaana input types Dhivehi on a Latin keyboard.
- Per-locale inputs for Dhivehi in the builder type Thaana and are right to left.
- Pages and styles use logical properties (`ms-`, `ps-`, `border-s`, `text-start`, `margin-inline-start`) only, so they work right to left. Keyboard ordering in the builder swaps the left and right arrows in RTL.
- The print page sets `dir="rtl"` for right-to-left locales.
- CSV exports start with a UTF-8 byte order mark, so spreadsheets read Thaana.

# For review

A Dhivehi speaker should review these lines; the rest are straightforward:

| English | Dhivehi | Note |
| --- | --- | --- |
| Repeating group | ތަކުރާރުވާ ގްރޫޕް | No settled term; "a group filled several times" |
| Multiple choice | ޚިޔާރުތަކުން އެކެއް | Describes the control ("one of the options") |
| One section at a time (wizard) | އެއް ފަހަރާ އެއް ސެކްޝަން (ވިޒާޑް) | |
| Depends on (field key) / Parent value | ބިނާވަނީ (ފީލްޑުގެ ކީ) / ބެލެނިވެރި އަގު | "Parent" for cascading selects |
| :item picked up / moved / dropped … | ނެގީ / ގެންދިޔައީ / ބެހެއްޓީ | Screen reader announcements while ordering |
| checked or filled / not checked or empty | ޗެކްކޮށްފައި ނުވަތަ ފުރާފައި / ޗެކްނުކޮށް ނުވަތަ ހުސްކޮށް | Condition operators |
| Placeholder | ޕްލޭސްހޯލްޑަރ | Transliterated |
| :label :number | :number ވަނަ :label | Row headings ("1 ވަނަ ރިފަރެންސް"); the order is swapped |
| {1} :count row\|[2,*] :count rows | :count ރޯ | |
