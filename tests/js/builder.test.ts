import { afterEach, describe, expect, it, vi } from "vitest"
import { initBuilder } from "../../resources/js/blade/builder"

function page() {
  document.body.innerHTML = `
    <div data-form-builder-builder data-csrf="token" data-strings='{"order_saved":"ތަރުތީބު ރައްކާކުރެވިއްޖެ","order_failed":"Failed","picked_up":":item ނެގީ"}'>
      <div class="sr-only" aria-live="assertive" data-form-builder-announcer></div>
      <ol data-form-builder-sortable data-order-url="/forms/x/sections/order" data-order-name="sections">
        <li data-form-builder-item="one" data-form-builder-item-label="One">
          <button type="button" data-form-builder-handle>≡</button>
          <ol data-form-builder-sortable data-order-url="/forms/x/sections/one/fields/order" data-order-name="fields">
            <li data-form-builder-item="a" data-form-builder-item-label="A"><button type="button" data-form-builder-handle id="handle-a">≡</button></li>
            <li data-form-builder-item="b" data-form-builder-item-label="B"><button type="button" data-form-builder-handle>≡</button></li>
          </ol>
        </li>
        <li data-form-builder-item="two" data-form-builder-item-label="Two"><button type="button" data-form-builder-handle>≡</button></li>
      </ol>
      <form method="POST" action="/forms/x/fields/a" id="delete-a" data-form-builder-confirm="Delete this?"></form>
    </div>`

  return document.querySelector<HTMLElement>("[data-form-builder-builder]")!
}

const key = (target: Element, key: string) =>
  target.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true }))

afterEach(() => {
  vi.useRealTimers()
  vi.unstubAllGlobals()
})

describe("the Blade builder script", () => {
  it("saves a field's new order without moving its section", async () => {
    vi.useFakeTimers()
    const fetch = vi.fn().mockResolvedValue({ ok: true })
    vi.stubGlobal("fetch", fetch)

    const root = page()
    const saved = vi.fn()
    root.addEventListener("form-builder:order", (event) => saved((event as CustomEvent).detail))
    initBuilder(root)

    const handle = document.getElementById("handle-a")!
    key(handle, " ")
    key(handle, "ArrowDown")
    key(handle, " ")

    await vi.runAllTimersAsync()

    expect(fetch).toHaveBeenCalledTimes(1)
    const [url, init] = fetch.mock.calls[0]!
    expect(url).toBe("/forms/x/sections/one/fields/order")
    expect(init.method).toBe("PUT")
    expect(JSON.parse(init.body)).toEqual({ fields: ["b", "a"] })
    expect(init.headers["X-CSRF-TOKEN"]).toBe("token")
    expect(saved).toHaveBeenCalledWith({ saved: true, name: "fields", keys: ["b", "a"] })

    const sections = Array.from(root.querySelectorAll(":scope > ol > li")).map((li) =>
      li.getAttribute("data-form-builder-item"),
    )
    expect(sections).toEqual(["one", "two"])
    expect(root.querySelector("[data-form-builder-announcer]")!.textContent).toBe("ތަރުތީބު ރައްކާކުރެވިއްޖެ")
  })

  it("asks before submitting a form that needs confirming", () => {
    const root = page()
    const cleanup = initBuilder(root)
    const confirm = vi.fn().mockReturnValue(false)
    vi.stubGlobal("confirm", confirm)

    const form = document.getElementById("delete-a") as HTMLFormElement
    const event = new SubmitEvent("submit", { bubbles: true, cancelable: true })
    form.dispatchEvent(event)

    expect(confirm).toHaveBeenCalledWith("Delete this?")
    expect(event.defaultPrevented).toBe(true)

    cleanup()
  })
})
