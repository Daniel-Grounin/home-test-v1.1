import React, { useState } from "react";
import { ToastContainer, toast } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import { motion } from "framer-motion";
import "./App.css"; // 👈 for the grid background

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
      const res = await fetch("http://localhost/HOME-TEST-v1.1/auth/request-otp.php", {
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
      const res = await fetch("http://localhost/HOME-TEST-v1.1/auth/verify-otp.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ name: username, email, code: otp }),
        credentials: "include",
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Invalid code");

      if (data?.token) {
        localStorage.setItem("authToken", data.token);
        localStorage.setItem("authTokenExp", data.expiresAt || "");
        localStorage.setItem("authUser", data.username || "");
      }

      toast.success("✅ Verified! Redirecting…", {
        autoClose: 1200,
        pauseOnHover: false,
        onClose: () => {
          window.location.href = "http://localhost/HOME-TEST-v1.1/index.php";
        },
      });
    } catch (err) {
      setError(err.message || "OTP verification failed.");
      toast.error(err.message || "OTP verification failed.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="relative h-screen w-screen overflow-hidden flex items-center justify-center font-sans bg-gradient-to-br from-[#0f172a] via-[#1a2e1a] to-[#0f172a]">
      <div className="absolute inset-0 bg-icons"></div> {/* 👈 WhatsApp icon grid */}
      <ToastContainer position="top-center" />
      {/* Greenish blobs for depth */}
      <div className="absolute w-[550px] h-[550px] bg-emerald-400/20 rounded-full blur-[140px] -top-40 -left-40 animate-[float_20s_ease-in-out_infinite]" />
      <div className="absolute w-[450px] h-[450px] bg-green-500/20 rounded-full blur-[120px] top-20 right-[-100px] animate-[float_24s_ease-in-out_infinite]" />
      <div className="absolute w-[350px] h-[350px] bg-teal-500/20 rounded-full blur-[100px] bottom-[-60px] left-1/3 animate-[float_28s_ease-in-out_infinite]" />
      {/* Glassmorphic Card */}
      <motion.div
        initial={{ opacity: 0, y: 40 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 1, ease: "easeOut" }}
        className="relative z-10 backdrop-blur-3xl bg-white/10 border border-white/20 rounded-3xl p-12 shadow-2xl w-[420px] text-center">
        <h2 className="text-4xl font-semibold text-white mb-4 tracking-tight">Welcome</h2>
        <p className="text-gray-200/80 mb-8 text-base">Enter your details to receive your login code.</p>

        {error && (
          <div className="bg-red-500/20 border border-red-400/50 text-red-200 p-3 rounded mb-4 text-sm">{error}</div>
        )}
        {message && (
          <div className="bg-green-500/20 border border-green-400/50 text-green-200 p-3 rounded mb-4 text-sm">
            {message}
          </div>
        )}

        {step === "request" ? (
          <form onSubmit={handleRequestOTP} className="flex flex-col gap-5">
            <input
              type="text"
              placeholder="Username"
              className="p-4 rounded-xl bg-white/5 border border-white/20 text-white text-lg placeholder-gray-400 focus:ring-2 focus:ring-emerald-400"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
            />
            <input
              type="email"
              placeholder="Email"
              className="p-4 rounded-xl bg-white/5 border border-white/20 text-white text-lg placeholder-gray-400 focus:ring-2 focus:ring-green-400"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
            />
            <motion.button
              whileHover={{ scale: 1.03 }}
              whileTap={{ scale: 0.97 }}
              type="submit"
              disabled={loading}
              className="p-4 mt-2 rounded-xl bg-gradient-to-r from-emerald-400 to-green-500 text-white text-lg font-semibold shadow-lg">
              {loading ? "Sending…" : "Send OTP"}
            </motion.button>
          </form>
        ) : (
          <form onSubmit={handleVerifyOTP} className="flex flex-col gap-5">
            <input
              type="text"
              placeholder="Enter OTP"
              maxLength={6}
              className="p-4 text-center rounded-xl bg-white/5 border border-white/20 text-white text-2xl tracking-[0.5em] placeholder-gray-400 focus:ring-2 focus:ring-emerald-400"
              value={otp}
              onChange={(e) => setOtp(e.target.value.replace(/[^0-9]/g, ""))}
            />
            <div className="flex gap-3">
              <button
                type="button"
                onClick={() => setStep("request")}
                className="flex-1 p-4 rounded-xl border border-white/20 text-white/80 hover:bg-white/10 text-lg">
                Change Email
              </button>
              <motion.button
                whileHover={{ scale: 1.03 }}
                whileTap={{ scale: 0.97 }}
                type="submit"
                disabled={loading}
                className="flex-1 p-4 rounded-xl bg-gradient-to-r from-green-400 to-emerald-500 text-white text-lg font-semibold shadow-lg">
                {loading ? "Verifying…" : "Verify & Continue"}
              </motion.button>
            </div>
          </form>
        )}
      </motion.div>
    </div>
  );
}
