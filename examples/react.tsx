import { StrictMode, useState } from "react"
import { createRoot } from "react-dom/client"
import { FormsProvider } from "@javaabu/forms"
import { html } from "@javaabu/forms/html"
import {
  FillShow,
  FormsShow,
  EntriesShow,
  type EntriesShowProps,
  type FillShowProps,
  type FormsShowProps,
} from "@javaabu/form-builder"
import entriesShow from "../tests/js/fixtures/entries-show.json"
import fillShow from "../tests/js/fixtures/fill-show.json"
import formsShow from "../tests/js/fixtures/forms-show.json"
import "../resources/css/form-builder.css"

const pages = {
  fill: "Fill a form (form-builder::Fill/Show)",
  builder: "The builder (form-builder::Forms/Show)",
  entry: "An entry, read-only (form-builder::Entries/Show)",
} as const

function App() {
  const [rtl, setRtl] = useState(false)
  const [page, setPage] = useState<keyof typeof pages>("fill")

  return (
    <main dir={rtl ? "rtl" : "ltr"} lang={rtl ? "dv" : "en"}>
      <header>
        <h1>React</h1>
        <p>
          <a href="./index.html">Blade example</a> ·{" "}
          <button type="button" onClick={() => setRtl(!rtl)}>
            RTL / ދިވެހި
          </button>
        </p>
        <nav>
          {Object.entries(pages).map(([key, label]) => (
            <button
              key={key}
              type="button"
              aria-pressed={page === key}
              onClick={() => setPage(key as keyof typeof pages)}
            >
              {label}
            </button>
          ))}
        </nav>
      </header>

      {page === "fill" ? <FillShow {...(fillShow as unknown as FillShowProps)} /> : null}
      {page === "builder" ? <FormsShow {...(formsShow as unknown as FormsShowProps)} /> : null}
      {page === "entry" ? <EntriesShow {...(entriesShow as unknown as EntriesShowProps)} /> : null}
    </main>
  )
}

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <FormsProvider ui={html}>
      <App />
    </FormsProvider>
  </StrictMode>,
)
