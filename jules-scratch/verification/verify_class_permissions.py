import re
from playwright.sync_api import sync_playwright, Page, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Go to the root, which might redirect to setup
    page.goto("http://localhost:8000/")

    # Check if we are on the setup page
    if "Setup" in page.title():
        page.get_by_label("Nom du Lycée").fill("Lycée de Test")
        page.get_by_label("Email de l'Administrateur").fill("admin@test.com")
        page.get_by_label("Mot de passe").fill("password")
        page.get_by_role("button", name="Installer").click()
        # After install, it should redirect to login
        expect(page).to_have_title(re.compile("Connexion"))

    # Now, login
    page.locator('input[name="email"]').fill("admin@test.com")
    page.locator('input[name="password"]').fill("password")
    page.get_by_role("button", name="Se Connecter").click()

    # Go to the classes page
    page.goto("http://localhost:8000/classes")
    expect(page.get_by_role("heading", name="Class Management")).to_be_visible()

    # Take screenshot
    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)