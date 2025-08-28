import React, { useState } from "react";
import "bootstrap/dist/css/bootstrap.min.css";
import { ToastContainer, toast } from "react-toastify"; // ← add toast
import "react-toastify/dist/ReactToastify.css";

export default function App() {
  const [step, setStep] = useState("request");
  const [username, setUsername] = useState("assaf");
  const [email, setEmail] = useState("dani.grunin@gmail.com");
  const [otp, setOtp] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");

  const validEmail = (e) => /.+@.+\..+/.test(e);

  async function handleRequestOTP(e) {
    e.preventDefault();
    setError("");
    setMessage("");

    if (!username.trim()) return setError("Please enter a username.");
    if (!validEmail(email)) return setError("Please enter a valid email address.");

    setLoading(true);
    try {
      const res = await fetch("http://localhost/HOME-TEST/auth/request-otp.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ email, name: username, hp: "" }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Failed to send OTP");

      setMessage("OTP sent to your email. It expires in 5 minutes.");
      toast.info("OTP sent! Check your inbox.");
      setStep("verify");
    } catch (err) {
      setError(err.message || "Failed to request OTP.");
      toast.error(err.message || "Failed to request OTP.");
    } finally {
      setLoading(false);
    }
  }

  async function handleVerifyOTP(e) {
    e.preventDefault();
    setError("");
    setMessage("");

    if (!otp.trim()) return setError("Enter the OTP code.");

    setLoading(true);
    try {
      const res = await fetch("http://localhost/HOME-TEST/auth/verify-otp.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ name: username, email, code: otp }),
        credentials: "include", // <-- crucial
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Invalid code");

      // ✅ persist token for later requests
      if (data?.token) {
        localStorage.setItem("authToken", data.token);
        localStorage.setItem("authTokenExp", data.expiresAt || "");
        localStorage.setItem("authUser", data.username || "");
      }

      toast.success("✅ Verified! Redirecting…", {
        autoClose: 1200,
        pauseOnHover: false,
        onClose: () => {
          window.location.href = "http://localhost/HOME-TEST/index.php";
        },
      });

      setTimeout(() => {
        if (!/\/HOME-TEST\/index\.php$/.test(window.location.href)) {
          window.location.href = "http://localhost/HOME-TEST/index.php";
        }
      }, 1400);
    } catch (err) {
      setError(err.message || "OTP verification failed.");
      toast.error(err.message || "OTP verification failed.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="container py-5">
      {/* Toast container must be mounted once */}
      <ToastContainer position="top-center" />

      <div className="row justify-content-center">
        <div className="col-12 col-sm-10 col-md-8 col-lg-6">
          <div className="card shadow-sm">
            <div className="card-body p-4">
              <img
                src="/assafmedia-logo.jpg"
                alt="AssafMedia Logo"
                className="mb-4"
                style={{ maxWidth: "350px", height: "auto", alignItems: "center" }}
              />{" "}
              <h1 className="h4 fw-bold mb-1">Login</h1>
              <p className="text-muted mb-4">Enter your username and email, then use the OTP sent to your inbox.</p>
              {error && <div className="alert alert-danger py-2">{error}</div>}
              {message && <div className="alert alert-success py-2">{message}</div>}
              {step === "request" ? (
                <form onSubmit={handleRequestOTP}>
                  <div className="mb-3">
                    <label htmlFor="username" className="form-label">
                      Username
                    </label>
                    <input
                      id="username"
                      type="text"
                      className="form-control"
                      placeholder="yourname"
                      autoComplete="username"
                      value={username}
                      onChange={(e) => setUsername(e.target.value)}
                    />
                  </div>

                  <div className="mb-3">
                    <label htmlFor="email" className="form-label">
                      Email
                    </label>
                    <input
                      id="email"
                      type="email"
                      className="form-control"
                      placeholder="you@example.com"
                      autoComplete="email"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                    />
                  </div>

                  <div>
                    {/* Honeypot field (hidden from real users) */}
                    <input type="text" name="hp" style={{ display: "none" }} tabIndex={-1} autoComplete="off" />
                  </div>

                  <button type="submit" className="btn btn-primary w-100" disabled={loading}>
                    {loading ? "Sending…" : "Send OTP"}
                  </button>
                </form>
              ) : (
                <form onSubmit={handleVerifyOTP}>
                  <div className="mb-3">
                    <label htmlFor="otp" className="form-label">
                      Enter OTP
                    </label>
                    <input
                      id="otp"
                      type="text"
                      inputMode="numeric"
                      pattern="[0-9]*"
                      className="form-control text-center"
                      placeholder="000000"
                      autoComplete="one-time-code"
                      maxLength={6}
                      value={otp}
                      onChange={(e) => setOtp(e.target.value.replace(/[^0-9]/g, ""))}
                    />
                    <div className="form-text">We sent a 6-digit code to {email}.</div>
                  </div>

                  <div className="d-flex justify-content-between gap-2">
                    <button type="button" className="btn btn-outline-secondary" onClick={() => setStep("request")}>
                      Change email
                    </button>
                    <button type="submit" className="btn btn-primary" disabled={loading}>
                      {loading ? "Verifying…" : "Verify & Continue"}
                    </button>
                  </div>
                </form>
              )}
              <hr className="my-4" />
              <ul className="small text-muted ps-3 mb-0">
                <li>
                  On success, it redirects to <code>/index.php</code>.
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
