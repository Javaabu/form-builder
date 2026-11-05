import type { StencilComponents } from "@javaabu/stencil"
import { FormBuilderContent } from "./Content"
import { FormBuilderRepeater } from "./Repeater"

/**
 * The stencil components javaabu/form-builder adds: pass them to `StencilForm`
 * (`components={formBuilderComponents}`) or register them once with `StencilProvider`
 */
export const formBuilderComponents: StencilComponents = {
  repeater: FormBuilderRepeater,
  "form-builder-content": FormBuilderContent,
}

export { FormBuilderContent, FormBuilderRepeater }
export { rowFromTemplate } from "./Repeater"
