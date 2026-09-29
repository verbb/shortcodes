Shortcodes lets content carry small, reusable instructions that Twig turns into project-owned output. Give authors concise tokens for components or dynamic values without allowing arbitrary template code in the field.

Register a shortcode name and handler, then let authors place that token within supported text. Attributes carry the few values the component needs while the rendering logic remains in project code.

## Features

- Represent a reusable component with a concise shortcode token.
- Pass controlled options without exposing Twig expressions to authors.
- Define what each shortcode means in module or template code.
- Update presentation centrally while stored content stays concise.
- Process shortcodes inside the content formats selected by the project.
- Use a shortcode within surrounding text or as a block-level component.
- Add the component vocabulary the site needs rather than a fixed catalogue.
