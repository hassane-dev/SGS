import re
from playwright.sync_api import sync_playwright, Page, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Go to the root page
    page.goto("http://localhost:8000/")

    # The application has a complex initial state. It might redirect to login,
    # setup, or a dashboard. We will handle these cases.
    page.wait_for_load_state('networkidle')
    title = page.title()

    if "Connexion" in title:
        page.locator('input[name="email"]').fill("HasMixiOne@mine.io")
        page.locator('input[name="password"]').fill("H@s7511mat9611")
        page.get_by_role("button", name="Se Connecter").click()
    elif "Setup" in title:
        page.get_by_label("Nom du Lycée").fill("Lycée de Démonstration")
        page.get_by_label("Email de l'Administrateur").fill("admin@demo.com")
        page.get_by_label("Mot de passe").fill("password")
        page.get_by_role("button", name="Installer").click()
        # After install, it redirects to login
        expect(page).to_have_title(re.compile("Connexion"))
        page.locator('input[name="email"]').fill("admin@demo.com")
        page.locator('input[name="password"]').fill("password")
        page.get_by_role("button", name="Se Connecter").click()

    # After login (or if already logged in), we should be on a dashboard-like page.
    # Now, navigate to the students page to see the full layout.
    page.goto("http://localhost:8000/eleves")

    # Assert that the new layout elements are visible
    expect(page.locator("#sidebar")).to_be_visible()
    expect(page.locator("#content nav.navbar")).to_be_visible()
    expect(page.get_by_role("heading", name="Gestion des Élèves")).to_be_visible()

    # Take a screenshot of the full page
    page.screenshot(path="jules-scratch/verification/new_layout_screenshot.png", full_page=True)

    browser.close()

with sync_playwright() as playwright:
    run(playwright)