<?php

namespace Javaabu\FormBuilder\Support;

/**
 * The strings the pages show, in the current locale. Blade views and React
 * pages get the same ones, so both stacks read the same.
 */
class Strings
{
    /**
     * @return array<string, string>
     */
    public static function common(): array
    {
        return [
            'back' => __('Back'),
            'cancel' => __('Cancel'),
            'edit' => __('Edit'),
            'delete' => __('Delete'),
            'save' => __('Save'),
            'view' => __('View'),
            'print' => __('Print'),
            'actions' => __('Actions'),
            'confirm_delete' => __('Delete this? This can\'t be undone.'),
            'previous_page' => __('Previous'),
            'next_page' => __('Next'),
            'page_of' => __('Page :page of :pages'),
        ];
    }

    /**
     * The forms list, settings and import pages
     *
     * @return array<string, string>
     */
    public static function forms(): array
    {
        return [
            ...static::common(),
            'title' => __('Forms'),
            'description' => __('Build forms, publish them and read their entries.'),
            'create' => __('New form'),
            'import' => __('Import'),
            'import_title' => __('Import a form'),
            'import_description' => __('Upload a form exported from the form builder, or paste its JSON. A form with the same key gets a new draft; otherwise a new form is made.'),
            'import_file' => __('Export file'),
            'import_json' => __('Or paste the JSON'),
            'import_key' => __('Key'),
            'import_key_help' => __('Leave empty to use the key in the export.'),
            'import_submit' => __('Import form'),
            'search' => __('Search forms'),
            'search_button' => __('Search'),
            'empty' => __('No forms yet.'),
            'no_results' => __('No forms match your search.'),
            'name' => __('Name'),
            'key' => __('Key'),
            'status' => __('Status'),
            'version' => __('Version'),
            'entries' => __('Entries'),
            'locked' => __('Defined in code'),
            'open_builder' => __('Open'),
        ];
    }

    /**
     * The builder, section, field, preview and versions pages
     *
     * @return array<string, string>
     */
    public static function builder(): array
    {
        return [
            ...static::common(),
            'forms' => __('Forms'),
            'settings' => __('Settings'),
            'preview' => __('Preview'),
            'publish' => __('Publish'),
            'confirm_publish' => __('Publish these changes? People will fill the new version from now on.'),
            'discard' => __('Discard changes'),
            'confirm_discard' => __('Discard the unpublished changes? The published version stays.'),
            'export' => __('Export'),
            'versions' => __('Versions'),
            'entries' => __('Entries'),
            'fill' => __('Open the form'),
            'draft_notice' => __('You\'re editing a draft. People keep filling version :number until you publish.'),
            'unpublished_notice' => __('This form isn\'t published yet. Publish it so people can fill it.'),
            'locked_notice' => __('This form is defined in code. Change it there; the builder only shows it.'),
            'wizard' => __('One section at a time (wizard)'),
            'add_section' => __('Add section'),
            'add_field' => __('Add field'),
            'add_field_to' => __('Add a field to :section'),
            'add_field_to_group' => __('Add a field to :group'),
            'choose_type' => __('Field type'),
            'no_fields' => __('No fields yet.'),
            'required' => __('Required'),
            'shown_when' => __('Shown when'),
            'depends_on' => __('Depends on :field'),
            'missing_type' => __('This field type isn\'t available any more.'),
            'move_up' => __('Move up'),
            'move_down' => __('Move down'),
            'move' => __('Move :item'),
            'field_key' => __('Key'),
            'section' => __('Section'),
            'step' => __('Step :number'),
            'instructions' => __('To move an item with the keyboard, press Space on its handle, use the arrow keys, then press Space again to drop it or Escape to cancel.'),
            'picked_up' => __(':item picked up. Position :position of :total.'),
            'moved' => __(':item moved to position :position of :total.'),
            'dropped' => __(':item dropped at position :position of :total.'),
            'cancelled' => __('Move cancelled. :item is back at position :position of :total.'),
            'order_saved' => __('Order saved.'),
            'order_failed' => __('The order couldn\'t be saved. Try again.'),
            'preview_title' => __('Preview of :form'),
            'preview_notice' => __('This is how the form looks. Submitting checks the answers but doesn\'t save them.'),
            'version_number' => __('Version :number'),
            'draft' => __('Draft'),
            'current' => __('Current'),
            'published_at' => __('Published'),
            'author' => __('By'),
            'fields_count' => __('Fields'),
            'entries_count' => __('Entries'),
            'assistant' => __('Assistant'),
            'assistant_title' => __('Build with the assistant'),
            'assistant_help' => __('Describe the form or the changes you want. The assistant changes the draft; review it before you publish.'),
            'assistant_prompt' => __('What should the form ask?'),
            'assistant_submit' => __('Ask the assistant'),
        ];
    }

    /**
     * The entries pages
     *
     * @return array<string, string>
     */
    public static function entries(): array
    {
        return [
            ...static::common(),
            'title' => __('Entries of :form'),
            'entry' => __('Entry :number'),
            'empty' => __('No entries yet.'),
            'export' => __('Export CSV'),
            'all_versions' => __('All versions'),
            'version' => __('Version'),
            'version_number' => __('Version :number'),
            'submitted_at' => __('Submitted'),
            'submitter' => __('Submitted by'),
            'guest' => __('Guest'),
            'filter' => __('Filter'),
            'files' => __('Files'),
            'form' => __('Form'),
            'details' => __('Details'),
            'number' => __('#'),
        ];
    }

    /**
     * The pages people fill forms on
     *
     * @return array<string, string>
     */
    public static function fill(): array
    {
        return [
            'closed' => __('This form isn\'t accepting entries.'),
            'thanks' => __('Thank you'),
            'again' => __('Fill it again'),
            'reference' => __('Your reference number is :number.'),
        ];
    }
}
