Shortcodes lets content carry small, reusable instructions that Twig turns into project-owned output. Give authors concise tokens for components or dynamic values without allowing arbitrary template code in the field.

Register a shortcode name and handler, then let authors place that token within supported text. Attributes carry the few values the component needs while the rendering logic remains in project code.

## Features

- **Familiar syntax:** Represent a reusable component with a concise shortcode token.
- **Attributes:** Pass controlled options without exposing Twig expressions to authors.
- **Project handlers:** Define what each shortcode means in module or template code.
- **Render-time output:** Update presentation centrally while stored content stays concise.
- **Text integration:** Process shortcodes inside the content formats selected by the project.
- **Inline or block output:** Use a shortcode within surrounding text or as a block-level component.
- **Extensible registry:** Add the component vocabulary the site needs rather than a fixed catalogue.
- **Project-owned rendering:** Resolve shortcodes through Twig or PHP at render time, making it possible to change presentation without editing every stored occurrence. Escaping and handler design remain under developer control.
