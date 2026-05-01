import importlib
import os
import tempfile
import unittest

from fastapi.testclient import TestClient


class FeedbackWebhookTest(unittest.TestCase):
    def test_webhook_review_positive(self):
        with tempfile.TemporaryDirectory() as tmp_dir:
            db_path = os.path.join(tmp_dir, "feedback_test.db")
            os.environ["FEEDBACK_AI_DB_URL"] = f"sqlite:///{db_path}"
            os.environ["ANTHROPIC_API_KEY"] = "test"

            appmod = importlib.import_module("delivery_feedback_ai")
            appmod.analyze_review = lambda payload: {
                "sentiment": "positive",
                "confidence": 0.95,
                "summary": "Fast delivery and elegant packaging.",
                "needs_support": False,
                "support_message": "",
            }

            client = TestClient(appmod.app)
            response = client.post(
                "/webhook/review",
                json={
                    "order_id": "ORD-1001",
                    "customer_name": "Amira",
                    "customer_email": "amira@example.com",
                    "review_text": "Loved the speed and care in packaging",
                    "rating": 5,
                },
            )

            self.assertEqual(response.status_code, 200)
            data = response.json()
            self.assertEqual(data["routed_to"], "testimonials")
            self.assertEqual(data["sentiment"], "positive")


if __name__ == "__main__":
    unittest.main()
