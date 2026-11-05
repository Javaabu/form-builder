import "../resources/js/blade/repeater"

const log = document.getElementById("log")!

// No server here: show the names and values the form would submit
document.getElementById("example-form")!.addEventListener("submit", (event) => {
  event.preventDefault()
  const data = new FormData(event.target as HTMLFormElement)
  log.textContent = Array.from(data.entries())
    .map(([name, value]) => `${name} = ${String(value)}`)
    .join("\n")
})

document.getElementById("direction")!.addEventListener("click", () => {
  const rtl = document.documentElement.dir !== "rtl"
  document.documentElement.dir = rtl ? "rtl" : "ltr"
  document.documentElement.lang = rtl ? "dv" : "en"
})
