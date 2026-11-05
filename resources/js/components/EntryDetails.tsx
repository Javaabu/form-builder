import type { EntryData, Strings } from "../types"

/** When, by whom and with which version an entry was submitted */
export function EntryDetails({ entry, strings }: { entry: EntryData; strings: Strings }): React.JSX.Element {
  return (
    <dl className="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-3">
      <div>
        <dt className="text-muted-foreground">{strings.submitted_at}</dt>
        <dd>{entry.submitted_at_label ?? "—"}</dd>
      </div>
      <div>
        <dt className="text-muted-foreground">{strings.submitter}</dt>
        <dd dir="auto">{entry.submitter ?? strings.guest}</dd>
      </div>
      <div>
        <dt className="text-muted-foreground">{strings.version}</dt>
        <dd>{entry.version}</dd>
      </div>
    </dl>
  )
}
