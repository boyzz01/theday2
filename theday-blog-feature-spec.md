# TheDay — Blog / Artikel Feature Spec

## Objective
Implement a blog / article feature for TheDay to support SEO, education, and top-of-funnel acquisition without changing the main landing page into a content-heavy page. The main landing page must remain focused on product conversion, while articles live in a separate content area such as `/blog`, `/inspirasi`, or `/artikel`.

This aligns with TheDay’s positioning as a premium, warm, modern, mobile-first digital wedding invitation platform for Indonesia. The article system should help attract users who are searching for wedding inspiration, planning tips, digital invitation guidance, and related pre-wedding topics.

## Product Direction
- Keep the landing page focused on conversion: hero, CTA, template previews, benefits, pricing, FAQ, and social proof.
- Add a dedicated article section in a separate route, recommended default: `/blog`.
- Add a lightweight article teaser section on the landing page, showing a small number of featured or latest articles.
- Use articles to support SEO around wedding planning, invitation ideas, wedding trends, budgeting, RSVP guidance, and digital invitation education.
- Ensure the article system supports future scale, category organization, and internal linking back to TheDay product pages.

## Routes
Implement these public routes:
- `GET /blog` — article index page
- `GET /blog/{slug}` — article detail page
- `GET /blog/category/{slug}` — optional category archive page
- `GET /blog/tag/{slug}` — optional tag archive page

If preferred, the route prefix may be `/artikel` or `/inspirasi`, but use one canonical public structure consistently.

## Landing Page Integration
Add a small content preview section near the lower part of the landing page.

Requirements:
- Show 3 featured or latest articles.
- Each card should contain: cover image, title, short excerpt, category, and publish date.
- Add CTA such as `Baca Artikel Lainnya` linking to the blog index.
- Keep the section compact so it does not distract from the main landing page conversion flow.
- On mobile, cards should remain clean and lightweight.

## Admin / CMS Requirements
Implement a simple admin content management flow for articles.

Minimum article fields:
- `title`
- `slug`
- `excerpt`
- `content` (rich text or markdown-supported content)
- `cover_image`
- `status` (`draft`, `published`, `scheduled` optional)
- `published_at`
- `author_name`
- `category_id`
- `meta_title`
- `meta_description`
- `featured` boolean

Optional fields:
- `tags`
- `reading_time`
- `canonical_url`
- `og_image`
- `schema_type`

Admin capabilities:
- Create article
- Edit article
- Save draft
- Publish article
- Unpublish article
- Mark article as featured
- Assign category
- Upload cover image
- Preview article before publish

## Category Strategy
Start with a small category set relevant to TheDay’s business and audience.

Recommended categories:
- Inspirasi Pernikahan
- Tips Persiapan Nikah
- Undangan Digital
- Dekorasi & Tema
- Budget & Checklist
- Tradisi & Acara

Do not create too many categories at the start.

## Content Scope
Articles must stay close to TheDay’s target audience and business context.

Good topics:
- Wedding trends in Indonesia
- Invitation wording and etiquette
- Wedding budgeting tips
- RSVP planning tips
- Save the date guidance
- Wedding theme inspiration
- Digital invitation comparisons
- Wedding planning checklists
- Timeline before wedding day
- Elegant invitation copy examples

Avoid topics that are too far from scope, such as general married life topics unrelated to invitation, planning, or wedding preparation.

## SEO Requirements
The article system should be SEO-friendly.

Implement:
- SEO-friendly slugs
- Unique meta title and meta description fields
- Open Graph tags
- Canonical URL support
- Proper heading structure in article content
- Internal links from articles to product pages and relevant articles
- Related article section on detail pages
- Sitemap inclusion for published articles
- Structured data if practical, such as `Article` schema

## Article Detail Page Requirements
Each article detail page should include:
- Cover image
- Title
- Category
- Publish date
- Author
- Main content
- Optional table of contents for long articles
- Related articles section
- CTA block to try TheDay product, for example:
  - `Coba Buat Undangan Gratis`
  - `Lihat Template Undangan`

The CTA should feel natural and relevant, not too sales-heavy.

## Blog Index Requirements
The blog index page should include:
- Search input
- Category filter
- Featured article area or highlighted posts
- List/grid of latest published articles
- Pagination or load more
- Mobile-friendly card layout

## Writing Style Guidance
All article presentation and UI copy should follow TheDay’s brand voice:
- warm
- elegant
- modern
- approachable
- natural Indonesian language

Do not use stiff corporate language.
Do not use clickbait style.
Do not make the blog look like a generic media portal.

## Technical Notes
The project stack uses Laravel 11 + Vue 3 + Inertia.js + Tailwind CSS.
Implement the article feature in a way that fits this architecture.

Suggested structure:
- Laravel models: `Article`, `Category`, optional `Tag`
- Laravel controllers for public article pages and admin CMS actions
- Inertia pages for blog index and article detail
- Admin form pages for create/edit/publish workflow
- Store images in configured storage and serve optimized versions if possible

## Suggested Database Design
### articles table
- `id`
- `title`
- `slug` unique
- `excerpt` nullable
- `content` long text
- `cover_image_path` nullable
- `status`
- `published_at` nullable
- `author_name` nullable
- `category_id` nullable
- `meta_title` nullable
- `meta_description` nullable
- `canonical_url` nullable
- `og_image_path` nullable
- `featured` boolean default false
- timestamps

### categories table
- `id`
- `name`
- `slug` unique
- `description` nullable
- timestamps

### optional tags tables
- `tags`
- `article_tag`

## Internal Linking Rules
Within articles, support internal CTA and links to:
- landing page
- pricing page
- template gallery
- create invitation flow
- related blog posts

This helps articles support acquisition while keeping the product journey connected.

## MVP Scope
For first release, implement only:
- blog index
- article detail page
- article categories
- admin create/edit/publish
- featured article teaser on landing page
- SEO meta fields

Leave these for later if needed:
- tags
- scheduled publishing
- table of contents auto-generation
- advanced search
- AI article generation
- analytics dashboard for articles

## Acceptance Criteria
The feature is considered complete when:
1. Public users can browse a blog index and open article detail pages.
2. Admin can create, edit, draft, and publish articles.
3. Landing page displays 3 article teasers linking to the blog.
4. Published articles are SEO-friendly and use clean slugs.
5. Blog content supports TheDay’s audience and product funnel without distracting from the landing page’s conversion purpose.
6. The UI remains elegant, lightweight, and mobile-friendly.

## Implementation Output Required
When implementing this feature, provide:
1. Database migrations
2. Models and relationships
3. Routes
4. Controllers
5. Inertia/Vue pages
6. Admin CMS pages/forms
7. Landing page article teaser integration
8. SEO/meta handling
9. Seeder or sample articles for testing
10. Notes on future extensibility
