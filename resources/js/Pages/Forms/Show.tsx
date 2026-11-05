import { useState } from "react"
import { router } from "@inertiajs/react"
import { Alert, Button, Card, Icon, LinkButton } from "@javaabu/forms"
import { AssistantCard } from "../../components/AssistantCard"
import { PageHeader } from "../../components/PageHeader"
import { SortableList, type SortableHandleProps } from "../../components/SortableList"
import { StatusAlert } from "../../components/StatusAlert"
import { StatusBadge } from "../../components/StatusBadge"
import { format } from "../../sortable"
import type { BuilderField, BuilderSection, FormsShowProps, InputTypeGroup, Strings } from "../../types"

const visit = { preserveScroll: true }

function confirmThen(message: string | undefined, action: () => void) {
  if (!message || window.confirm(message)) action()
}

function Handle({ handle, label, strings }: { handle: SortableHandleProps; label: string; strings: Strings }) {
  return (
    <button
      type="button"
      className="text-muted-foreground hover:bg-muted flex size-8 shrink-0 cursor-grab items-center justify-center rounded-md focus-visible:outline-2"
      aria-label={format(strings.move ?? "Move :item", { item: label })}
      {...handle}
    >
      <Icon name="arrow-up-down" />
    </button>
  )
}

function MoveButtons({
  item,
  label,
  strings,
}: {
  item: { is_first: boolean; is_last: boolean; urls: { move?: string } }
  label: string
  strings: Strings
}) {
  if (!item.urls.move) return null
  const url = item.urls.move

  return (
    <>
      {!item.is_first ? (
        <button
          type="button"
          className="text-muted-foreground hover:bg-muted flex size-8 items-center justify-center rounded-md"
          aria-label={`${strings.move_up}: ${label}`}
          title={strings.move_up}
          onClick={() => router.post(url, { direction: "up" }, visit)}
        >
          <Icon name="arrow-up" />
        </button>
      ) : null}
      {!item.is_last ? (
        <button
          type="button"
          className="text-muted-foreground hover:bg-muted flex size-8 items-center justify-center rounded-md"
          aria-label={`${strings.move_down}: ${label}`}
          title={strings.move_down}
          onClick={() => router.post(url, { direction: "down" }, visit)}
        >
          <Icon name="arrow-down" />
        </button>
      ) : null}
    </>
  )
}

function AddField({
  url,
  types,
  label,
  id,
  strings,
}: {
  url: string
  types: InputTypeGroup[]
  label: string
  id: string
  strings: Strings
}) {
  const [type, setType] = useState(types[0]?.types[0]?.key ?? "")

  const open = () => {
    const target = new URL(url, window.location.origin)
    target.searchParams.set("type", type)
    router.get(target.pathname + target.search)
  }

  return (
    <div className="flex flex-wrap items-end gap-2" data-form-builder-add-field="">
      <div className="flex min-w-48 flex-col gap-1">
        <label htmlFor={id} className="text-sm font-medium">
          {label}
        </label>
        <select
          id={id}
          value={type}
          onChange={(event) => setType(event.target.value)}
          className="bg-background h-9 rounded-md border px-3"
        >
          {types.map((group) => (
            <optgroup key={group.key} label={group.label}>
              {group.types.map((option) => (
                <option key={option.key} value={option.key}>
                  {option.name}
                </option>
              ))}
            </optgroup>
          ))}
        </select>
      </div>
      <Button type="button" color="outline-secondary" startIcon="plus" onClick={open}>
        {strings.add_field}
      </Button>
    </div>
  )
}

function FieldItem({
  field,
  handle,
  editable,
  groupTypes,
  strings,
}: {
  field: BuilderField
  handle: SortableHandleProps
  editable: boolean
  groupTypes: InputTypeGroup[]
  strings: Strings
}) {
  return (
    <div className="bg-background flex flex-col gap-2 rounded-md border p-3" data-form-builder-field={field.path}>
      <div className="flex items-center gap-3">
        {editable ? <Handle handle={handle} label={field.label} strings={strings} /> : null}

        <div className="flex min-w-0 flex-1 flex-col">
          <span className="truncate font-medium" dir="auto">
            {field.label}
            {field.required ? (
              <>
                <span className="text-destructive" aria-hidden="true">
                  {" "}
                  *
                </span>
                <span className="sr-only">({strings.required})</span>
              </>
            ) : null}
          </span>
          <span className="text-muted-foreground text-sm">
            {field.type_name} · <span dir="ltr">{field.path}</span>
            {field.depends_on ? ` · ${format(strings.depends_on ?? ":field", { field: field.depends_on })}` : null}
          </span>
          {field.missing_type ? <span className="text-destructive text-sm">{strings.missing_type}</span> : null}
          {field.conditions.map((condition) => (
            <span key={condition} className="text-muted-foreground text-sm" dir="auto">
              {strings.shown_when}: {condition}
            </span>
          ))}
        </div>

        {editable ? (
          <div className="flex shrink-0 flex-wrap items-center gap-1">
            <MoveButtons item={field} label={field.label} strings={strings} />
            {field.urls.edit ? (
              <LinkButton url={field.urls.edit} color="outline-secondary" size="sm">
                {strings.edit}
              </LinkButton>
            ) : null}
            {field.urls.destroy ? (
              <Button
                type="button"
                color="danger"
                size="sm"
                onClick={() =>
                  confirmThen(strings.confirm_delete, () => router.delete(field.urls.destroy as string, visit))
                }
              >
                {strings.delete}
              </Button>
            ) : null}
          </div>
        ) : null}
      </div>

      {field.has_fields ? (
        <div className="flex flex-col gap-2 border-s-2 ps-4">
          <FieldList
            fields={field.fields}
            label={field.label}
            orderUrl={field.urls.order_fields}
            editable={editable}
            groupTypes={groupTypes}
            strings={strings}
          />
          {editable && field.urls.create_field ? (
            <AddField
              url={field.urls.create_field}
              types={groupTypes}
              label={format(strings.add_field_to_group ?? ":group", { group: field.label })}
              id={`form-builder-add-${field.key}`}
              strings={strings}
            />
          ) : null}
        </div>
      ) : null}
    </div>
  )
}

function FieldList({
  fields,
  label,
  orderUrl,
  editable,
  groupTypes,
  strings,
}: {
  fields: BuilderField[]
  label: string
  orderUrl?: string
  editable: boolean
  groupTypes: InputTypeGroup[]
  strings: Strings
}) {
  if (fields.length === 0) return <p className="text-muted-foreground text-sm">{strings.no_fields}</p>

  return (
    <SortableList
      items={fields}
      getKey={(field) => field.key}
      getTitle={(field) => field.label}
      disabled={!editable || !orderUrl}
      strings={strings}
      instructionsId="form-builder-instructions"
      aria-label={label}
      className="flex flex-col gap-2"
      onReorder={(ordered) => orderUrl && router.put(orderUrl, { fields: ordered.map((field) => field.key) }, visit)}
      renderItem={(field, handle) => (
        <FieldItem field={field} handle={handle} editable={editable} groupTypes={groupTypes} strings={strings} />
      )}
    />
  )
}

function SectionCard({
  section,
  index,
  handle,
  wizard,
  editable,
  deletable,
  types,
  groupTypes,
  strings,
}: {
  section: BuilderSection
  index: number
  handle: SortableHandleProps
  wizard: boolean
  editable: boolean
  deletable: boolean
  types: InputTypeGroup[]
  groupTypes: InputTypeGroup[]
  strings: Strings
}) {
  return (
    <Card>
      <div className="flex flex-col gap-4" data-form-builder-section={section.key}>
        <div className="flex items-start gap-3">
          {editable ? <Handle handle={handle} label={section.title} strings={strings} /> : null}
          <div className="flex min-w-0 flex-1 flex-col gap-1">
            <h2 className="text-lg font-semibold" dir="auto">
              {wizard ? (
                <span className="text-muted-foreground">
                  {format(strings.step ?? ":number", { number: index + 1 })} ·{" "}
                </span>
              ) : null}
              {section.title}
            </h2>
            {section.description ? (
              <p className="text-muted-foreground text-sm" dir="auto">
                {section.description}
              </p>
            ) : null}
            {section.conditions.map((condition) => (
              <p key={condition} className="text-muted-foreground text-sm" dir="auto">
                {strings.shown_when}: {condition}
              </p>
            ))}
          </div>
          {editable ? (
            <div className="flex shrink-0 flex-wrap items-center gap-1">
              <MoveButtons item={section} label={section.title} strings={strings} />
              {section.urls.edit ? (
                <LinkButton url={section.urls.edit} color="outline-secondary" size="sm">
                  {strings.edit}
                </LinkButton>
              ) : null}
              {deletable && section.urls.destroy ? (
                <Button
                  type="button"
                  color="danger"
                  size="sm"
                  onClick={() =>
                    confirmThen(strings.confirm_delete, () => router.delete(section.urls.destroy as string, visit))
                  }
                >
                  {strings.delete}
                </Button>
              ) : null}
            </div>
          ) : null}
        </div>

        <FieldList
          fields={section.fields}
          label={section.title}
          orderUrl={section.urls.order_fields}
          editable={editable}
          groupTypes={groupTypes}
          strings={strings}
        />

        {editable && section.urls.create_field ? (
          <AddField
            url={section.urls.create_field}
            types={types}
            label={format(strings.add_field_to ?? ":section", { section: section.title })}
            id={`form-builder-add-${section.key}`}
            strings={strings}
          />
        ) : null}
      </div>
    </Card>
  )
}

/** form-builder::Forms/Show — the builder: sections and fields, with every action */
export default function Show({
  form,
  form_layout,
  sections,
  types,
  group_types,
  can,
  urls,
  status,
  strings,
}: FormsShowProps): React.JSX.Element {
  const editable = can.update
  const wizard = form_layout === "wizard"

  return (
    <div className="flex flex-col gap-6" data-form-builder-builder="">
      <PageHeader
        title={form.name}
        description={form.description}
        backUrl={urls.index}
        backLabel={strings.forms}
        actions={
          <>
            <StatusBadge form={form} />
            {editable && urls.edit ? (
              <LinkButton url={urls.edit} color="outline-secondary" startIcon="pencil">
                {strings.settings}
              </LinkButton>
            ) : null}
            {urls.preview ? (
              <LinkButton url={urls.preview} color="outline-secondary" startIcon="eye">
                {strings.preview}
              </LinkButton>
            ) : null}
            {can.view_entries && urls.entries ? (
              <LinkButton url={urls.entries} color="outline-secondary">
                {strings.entries} ({form.entries_count})
              </LinkButton>
            ) : null}
            {urls.versions ? (
              <LinkButton url={urls.versions} color="outline-secondary">
                {strings.versions}
              </LinkButton>
            ) : null}
            {urls.export ? (
              <a href={urls.export} className="text-sm hover:underline" download>
                {strings.export}
              </a>
            ) : null}
            {form.urls.fill ? (
              <LinkButton url={form.urls.fill} color="outline-secondary" startIcon="external-link">
                {strings.fill}
              </LinkButton>
            ) : null}
            {can.publish && urls.publish ? (
              <Button
                type="button"
                startIcon="check"
                onClick={() =>
                  confirmThen(strings.confirm_publish, () => router.post(urls.publish as string, {}, visit))
                }
              >
                {strings.publish}
              </Button>
            ) : null}
          </>
        }
      />

      <StatusAlert status={status} />

      {form.is_locked ? (
        <Alert type="info">{strings.locked_notice}</Alert>
      ) : form.has_draft && form.published_version ? (
        <Alert type="warning">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <span>{format(strings.draft_notice ?? "", { number: form.published_version })}</span>
            {editable && urls.discard ? (
              <Button
                type="button"
                color="outline-secondary"
                size="sm"
                onClick={() => confirmThen(strings.confirm_discard, () => router.delete(urls.discard as string, visit))}
              >
                {strings.discard}
              </Button>
            ) : null}
          </div>
        </Alert>
      ) : !form.published_version ? (
        <Alert type="info">{strings.unpublished_notice}</Alert>
      ) : null}

      {wizard ? <p className="text-muted-foreground text-sm">{strings.wizard}</p> : null}

      <p id="form-builder-instructions" className="sr-only">
        {strings.instructions}
      </p>

      <SortableList
        items={sections}
        getKey={(section) => section.key}
        getTitle={(section) => section.title}
        disabled={!editable || !urls.order_sections}
        strings={strings}
        instructionsId="form-builder-instructions"
        aria-label={strings.section}
        className="flex flex-col gap-4"
        onReorder={(ordered) =>
          urls.order_sections &&
          router.put(urls.order_sections, { sections: ordered.map((section) => section.key) }, visit)
        }
        renderItem={(section, handle) => (
          <SectionCard
            section={section}
            index={sections.indexOf(section)}
            handle={handle}
            wizard={wizard}
            editable={editable}
            deletable={sections.length > 1}
            types={types}
            groupTypes={group_types}
            strings={strings}
          />
        )}
      />

      {editable && urls.create_section ? (
        <div>
          <LinkButton url={urls.create_section} color="outline-secondary" startIcon="plus">
            {strings.add_section}
          </LinkButton>
        </div>
      ) : null}
      {urls.assistant ? <AssistantCard url={urls.assistant} strings={strings} /> : null}
    </div>
  )
}
