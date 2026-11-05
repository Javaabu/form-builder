import type { StencilSchema } from "@javaabu/stencil"

export type Strings = Record<string, string>

export interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export type FormStatus = "draft" | "published" | "changed"

/** A form as the pages list it (BuilderData::form()) */
export interface FormData {
  id: number
  key: string
  name: string
  description: string | null
  status: FormStatus
  status_label: string
  is_locked: boolean
  accepts_entries: boolean
  published_version: number | null
  draft_version: number | null
  has_draft: boolean
  entries_count: number
  updated_at: string | null
  urls: Partial<Record<"show" | "edit" | "entries" | "preview" | "destroy" | "fill", string>>
}

export interface VersionData {
  id: number
  number: number
  is_draft: boolean
  is_current: boolean
  published_at: string | null
  author: string | null
  entries_count: number
  fields_count: number
}

export interface InputTypeData {
  key: string
  name: string
  icon: string
  group: string
  takes_input: boolean
  has_options: boolean
  has_fields: boolean
  watchable: boolean
  checkbox: boolean
}

export interface InputTypeGroup {
  key: string
  label: string
  types: InputTypeData[]
}

export interface BuilderField {
  key: string
  path: string
  type: string
  type_name: string
  icon: string | null
  label: string
  required: boolean
  missing_type: boolean
  has_fields: boolean
  depends_on: string | null
  conditions: string[]
  position: number
  is_first: boolean
  is_last: boolean
  urls: Partial<Record<"edit" | "destroy" | "move" | "create_field" | "order_fields", string>>
  fields: BuilderField[]
}

export interface BuilderSection {
  key: string
  title: string
  description: string | null
  columns: number | null
  conditions: string[]
  position: number
  is_first: boolean
  is_last: boolean
  urls: Partial<Record<"edit" | "destroy" | "move" | "create_field" | "order_fields", string>>
  fields: BuilderField[]
}

interface PageProps {
  status?: string | null
  strings: Strings
}

/** form-builder::Forms/Index */
export interface FormsIndexProps extends PageProps {
  forms: FormData[]
  pagination: Pagination
  search: string
  can: { create: boolean }
  urls: { index: string; create?: string; import?: string; assistant?: string }
}

/** form-builder::Forms/Edit: a new form, or a form's settings */
export interface FormsEditProps extends PageProps {
  form: StencilSchema
  record: FormData | null
  title: string
  back_url: string
}

/** form-builder::Forms/Show: the builder */
export interface FormsShowProps extends PageProps {
  form: FormData
  version: VersionData | null
  form_layout: "sections" | "wizard"
  sections: BuilderSection[]
  types: InputTypeGroup[]
  group_types: InputTypeGroup[]
  can: { update: boolean; publish: boolean; delete: boolean; view_entries: boolean }
  urls: Partial<
    Record<
      | "index"
      | "edit"
      | "preview"
      | "versions"
      | "export"
      | "entries"
      | "publish"
      | "discard"
      | "destroy"
      | "create_section"
      | "order_sections"
      | "structure"
      | "assistant",
      string
    >
  >
}

/** form-builder::Forms/Preview */
export interface FormsPreviewProps extends PageProps {
  form: StencilSchema
  record: FormData
  version: VersionData
  title: string
  description: string | null
  back_url: string
}

/** form-builder::Forms/Versions */
export interface FormsVersionsProps extends PageProps {
  record: FormData
  versions: VersionData[]
  back_url: string
}

/** form-builder::Forms/Import */
export interface FormsImportProps extends PageProps {
  action: string
  back_url: string
}

/** form-builder::Sections/Edit */
export interface SectionsEditProps extends PageProps {
  form: StencilSchema
  record: FormData
  section: Record<string, unknown> | null
  title: string
  back_url: string
}

/** form-builder::Fields/Edit */
export interface FieldsEditProps extends PageProps {
  form: StencilSchema
  record: FormData
  type: InputTypeData
  field: Record<string, unknown> | null
  title: string
  back_url: string
}

export interface EntryData {
  id: number
  status: string
  version: number
  submitted_at: string | null
  submitted_at_label: string | null
  submitter: string | null
  values: Record<string, string | number | null>
  urls: { show: string; print: string; destroy: string }
}

/** form-builder::Entries/Index */
export interface EntriesIndexProps extends PageProps {
  record: FormData
  entries: EntryData[]
  columns: Record<string, string>
  versions: number[]
  version: number | null
  pagination: Pagination
  can: { export: boolean; delete: boolean }
  urls: { index: string; export: string; form: string }
}

/** form-builder::Entries/Show */
export interface EntriesShowProps extends PageProps {
  record: FormData
  entry: EntryData
  display: StencilSchema
  files: { label: string; name: string; url: string }[]
  can: { delete: boolean }
  urls: { index: string; print: string; destroy: string }
}

/** form-builder::Fill/Show */
export interface FillShowProps extends PageProps {
  form: StencilSchema | null
  open: boolean
  title: string
  description: string | null
  wizard: boolean
}

/** form-builder::Fill/Thanks */
export interface FillThanksProps extends PageProps {
  title: string
  message: string
  entry: number | null
  form_url: string | null
}
