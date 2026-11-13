# Glow Beauty Salon

A responsive beauty salon website built with WordPress and the free Astra theme. It has a Home page, a Services page with a price list, a Gallery, and a Contact page with an online appointment booking form. No page builder and no third-party plugins: all the custom work is in one small plugin.

> The treatments, prices and opening hours are sample content for a portfolio project. Add your own contact details before using it for a real business.

## Screenshots
Add images to `docs/screenshots/` and link them here, for example:

```
![Home](docs/screenshots/home.png)
![Services](docs/screenshots/services.png)
```

## Features
- Responsive layout for desktop, tablet and phone
- Home page with photo hero, service tiles, gallery, opening hours and call-to-action
- Services page with a clear price list in four groups
- Gallery page
- Appointment booking form (name, email, phone, service, date, message) with nonce check, spam trap, server-side validation and sanitised input; requests are saved in the WordPress admin under "Bookings"
- Highlighted "Book Now" button in the menu, scroll-to-top button, clean footer

## Tech
WordPress, Astra (free), PHP (custom plugin, shortcodes, custom post type, activation hook), CSS, WAMP (local server).

## What is in this repo
Only my own work is stored here, not the full WordPress install.

| Path | Content |
| --- | --- |
| `plugin/glow-salon-setup/` | Custom plugin: creates pages, menu and styling, plus the booking form and gallery shortcode |
| `plugin/glow-salon-setup/css/glow-salon.css` | Custom plum, rose and gold styling |
| `plugin/glow-salon-setup/images/`, `assets/` | Gallery and background images |
| `docs/screenshots/` | Screenshots of the finished site |

## Run it locally
1. Install WordPress with WAMP (for example in `C:\wamp64\www\salon`, database `salon_db`).
2. Install and activate the Astra theme.
3. Zip the folder `plugin/glow-salon-setup`, then Plugins > Add New > Upload Plugin, and activate it.
4. Settings > Permalinks > Post name > Save.

## What I learned
Building a service-business website in WordPress; writing a plugin with shortcodes, a custom post type and a validated booking form; responsive CSS; and using Git and GitHub safely (secrets such as `wp-config.php` are never committed).

## Credits
Photos from Unsplash (free to use under the Unsplash License):
- Makeup flat lay: Jazmin Quaynor
- Hair colour: Kareya Saleh
- Haircut: Daria Andriianova
- Facial and spa: Engin Akyurt
- Manicure: De Aura
- Pedicure: Rune Enstad
