import React from "react";
import { APP_NAME, APP_TAGLINE } from "../../../shared/config/branding";

const ParticlesAuth = ({ children }: { children: React.ReactNode }) => (
  <div className="auth-page-wrapper pt-5">
    <div className="auth-one-bg-position auth-one-bg" id="auth-particles"><div className="bg-overlay" /></div>
    {children}
    <footer className="footer"><div className="container"><div className="row"><div className="col-lg-12"><div className="text-center"><p className="mb-0 text-muted">&copy; {new Date().getFullYear()} {APP_NAME}. {APP_TAGLINE}</p></div></div></div></div></footer>
  </div>
);

export default ParticlesAuth;
