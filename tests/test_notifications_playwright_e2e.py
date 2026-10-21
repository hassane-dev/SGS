import sys
import time
import subprocess
import sqlite3
from playwright.sync_api import sync_playwright

def run_e2e_tests():
    server_proc = subprocess.Popen(
        ["php", "-S", "127.0.0.1:8089", "-t", "public"],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL
    )
    time.sleep(1)

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(
                headless=True,
                args=["--autoplay-policy=user-gesture-required"]
            )
            context = browser.new_context()
            page = context.new_page()

            console_logs = []
            page.on("console", lambda msg: console_logs.append(f"[{msg.type}] {msg.text}"))

            print("\n=========================================================")
            print(" RUNNING PLAYWRIGHT FRONTEND NOTIFICATION E2E SUITE")
            print("=========================================================")

            # Setup user password & insert SCENARIO 3 old unread notification BEFORE login
            conn = sqlite3.connect("database.sqlite")
            cursor = conn.cursor()
            cursor.execute("UPDATE utilisateurs SET mot_de_passe = '$2y$10$VY9G3uS27aXppEk9DOoI7.o8wB49gY3pasL1b1om2YjKZkyikwWHe' WHERE email = 'p.nguema@lte-test.com'")
            cursor.execute("DELETE FROM notifications WHERE lycee_id = 9926")

            # --- SCENARIO 3: Old unread notification on initial session navigation ---
            print("\n--- SCENARIO 3: Old unread notification on initial session navigation ---")
            cursor.execute(
                "INSERT INTO notifications (id, user_id, lycee_id, message, link, is_read, created_at) VALUES (100, 10324, 9926, 'Ancienne notification non lue', '/dashboard', 0, datetime('now', '-10 minutes'))"
            )
            conn.commit()

            # Login
            page.goto("http://127.0.0.1:8089/login")
            page.fill("input[name='email']", "p.nguema@lte-test.com")
            page.fill("input[name='password']", "password123")
            page.click("button[type='submit']")
            page.wait_for_timeout(1500)

            print("   Logged in successfully. Current URL:", page.url)

            badge_text = page.evaluate("() => document.getElementById('notification-badge')?.textContent")
            toasts_sc3 = page.evaluate("() => document.querySelectorAll('.sgs-toast').length")
            print(f"   [PASS] Old unread notification shown in badge counter ({badge_text}), Toast count = {toasts_sc3} (no intrusive toast for old notification).")
            assert badge_text == "1", f"Expected badge = 1, got {badge_text}"
            assert toasts_sc3 == 0, f"Expected 0 toasts for old notification, got {toasts_sc3}"

            # --- SCENARIO 1: Notification created right before a redirect / navigation ---
            print("\n--- SCENARIO 1: Notification created right before navigation / redirect ---")
            # Insert notification ID 101 created during business action before redirect
            cursor.execute(
                "INSERT INTO notifications (id, user_id, lycee_id, message, link, is_read, created_at) VALUES (101, 10324, 9926, 'Notification créée avant redirection', '/eleves', 0, datetime('now'))"
            )
            conn.commit()

            # User navigates/redirects to /eleves
            page.goto("http://127.0.0.1:8089/eleves")
            page.wait_for_timeout(1500)

            toasts_sc1 = page.evaluate("() => Array.from(document.querySelectorAll('.sgs-toast')).map(e => e.textContent)")
            badge_text_sc1 = page.evaluate("() => document.getElementById('notification-badge')?.textContent")
            print(f"   [PASS] Notification created before redirect detected on new page! Badge = {badge_text_sc1}, Toasts = {len(toasts_sc1)}")
            assert len(toasts_sc1) == 1, f"Expected 1 toast on target page, got {len(toasts_sc1)}"
            assert "Notification créée avant redirection" in toasts_sc1[0]

            # --- SCENARIO 4: No toast repetition on subsequent polls ---
            print("\n--- SCENARIO 4: No toast repetition on subsequent polling ---")
            page.evaluate("() => window.sgsTriggerPoll()")
            page.wait_for_timeout(1000)

            toasts_sc4 = page.evaluate("() => Array.from(document.querySelectorAll('.sgs-toast')).map(e => e.textContent)")
            print(f"   [PASS] Subsequent poll did not duplicate toast! Total toasts in DOM = {len(toasts_sc4)}")
            assert len(toasts_sc4) == 1, f"Expected exactly 1 toast without duplicates, got {len(toasts_sc4)}"

            # --- SCENARIO 2: Notification created while user stays on page ---
            print("\n--- SCENARIO 2: Notification created while user stays stationary on page ---")
            cursor.execute(
                "INSERT INTO notifications (id, user_id, lycee_id, message, link, is_read, created_at) VALUES (102, 10324, 9926, 'Notification temps réel stationnaire', '/dashboard', 0, datetime('now'))"
            )
            conn.commit()

            page.evaluate("() => window.sgsTriggerPoll()")
            page.wait_for_timeout(1000)

            toasts_sc2 = page.evaluate("() => Array.from(document.querySelectorAll('.sgs-toast')).map(e => e.textContent)")
            print(f"   [PASS] Stationary notification detected via polling! Total toasts in DOM = {len(toasts_sc2)}")
            assert len(toasts_sc2) == 2, f"Expected 2 toasts, got {len(toasts_sc2)}"

            # --- SCENARIO 5: Multiple notifications received in a single polling ---
            print("\n--- SCENARIO 5: Multiple notifications received in a single polling ---")
            cursor.execute(
                "INSERT INTO notifications (id, user_id, lycee_id, message, link, is_read, created_at) VALUES (103, 10324, 9926, 'Multiple Notif A', '/linkA', 0, datetime('now'))"
            )
            cursor.execute(
                "INSERT INTO notifications (id, user_id, lycee_id, message, link, is_read, created_at) VALUES (104, 10324, 9926, 'Multiple Notif B', '/linkB', 0, datetime('now'))"
            )
            conn.commit()

            page.evaluate("() => window.sgsTriggerPoll()")
            page.wait_for_timeout(1000)

            badge_text_sc5 = page.evaluate("() => document.getElementById('notification-badge')?.textContent")
            toasts_sc5 = page.evaluate("() => Array.from(document.querySelectorAll('.sgs-toast')).map(e => e.textContent)")
            print(f"   [PASS] Multiple notifications in single poll handled! Final Badge = {badge_text_sc5}, Visible Toasts = {len(toasts_sc5)} (max 3 displayed)")
            assert badge_text_sc5 == "5", f"Expected badge = 5, got {badge_text_sc5}"

            # Clean test data
            cursor.execute("DELETE FROM notifications WHERE lycee_id = 9926")
            conn.commit()
            conn.close()

            print("\n=========================================================")
            print(" ALL PLAYWRIGHT FRONTEND NOTIFICATION SCENARIOS PASSED 100%")
            print("=========================================================\n")

            browser.close()
    finally:
        server_proc.terminate()

if __name__ == "__main__":
    run_e2e_tests()
