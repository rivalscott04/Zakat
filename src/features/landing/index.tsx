import { Link } from "react-router-dom";
import { Container } from "reactstrap";
import logo from "../../assets/images/zetra-logo-light.svg";
import heroImage from "../../assets/images/landing/zakat-hero.png";
import "./landing.css";

const Icon = ({ name }: { name: "coin" | "safe" | "people" }) => {
  const paths = {
    coin: (
      <>
        <circle cx="12" cy="12" r="8" />
        <path d="M12 7v10M9.5 9.5c.6-1.2 4.5-1.2 5.1 0 .7 1.5-5.7 1.5-5 3 .6 1.5 4.8 1.5 5.3 0" />
      </>
    ),
    safe: (
      <>
        <rect x="4" y="5" width="16" height="14" rx="2" />
        <circle cx="12" cy="12" r="3" />
        <path d="M12 9v3l2 1" />
      </>
    ),
    people: (
      <>
        <circle cx="9" cy="8" r="3" />
        <circle cx="17" cy="10" r="2.5" />
        <path d="M3.5 19c.3-3 2.1-5 5.5-5s5.2 2 5.5 5M14 15c3.4-.8 5.7.7 6.5 3.5" />
      </>
    ),
  };
  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.8"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      {paths[name]}
    </svg>
  );
};

const LandingPage = () => {
  document.title = "ZETRA — Kelola Zakat dengan Amanah";

  return (
    <div className="landing-zakat bg-white">
      <header className="landing-nav position-absolute top-0 start-0 end-0 z-3">
        <Container className="d-flex align-items-center justify-content-between py-4">
          <Link to="/" aria-label="ZETRA beranda">
            <img src={logo} alt="ZETRA" height="30" />
          </Link>
          <nav className="d-flex align-items-center gap-4">
            <a href="#manfaat" className="landing-nav-link d-none d-md-inline">
              Mengapa ZETRA
            </a>
            <a href="#alur" className="landing-nav-link d-none d-md-inline">
              Cara kerja
            </a>
            <Link to="/login" className="landing-nav-button">
              Masuk
            </Link>
          </nav>
        </Container>
      </header>

      <main>
        <section className="landing-hero position-relative overflow-hidden">
          <img
            className="landing-hero-image position-absolute top-0 start-0 w-100 h-100"
            src={heroImage}
            alt="Beragam sumber zakat dikelola transparan dan disalurkan kepada penerima"
          />
          <div className="landing-hero-shade position-absolute top-0 start-0 w-100 h-100" />
          <Container className="position-relative h-100 d-flex align-items-end pb-5 pb-lg-6">
            <div className="landing-hero-copy text-white">
              <p className="landing-eyebrow mb-3">
                PENGELOLAAN ZAKAT YANG AMANAH
              </p>
              <h1 className="display-3 fw-bold mb-4">
                Dari amanah,
                <br />
                <span>menjadi manfaat.</span>
              </h1>
              <p className="landing-hero-lead mb-4">
                Kelola zakat fitrah, harta, perdagangan, penghasilan, dan
                penyalurannya dalam satu alur yang rapi dan mudah
                dipertanggungjawabkan.
              </p>
              <div className="d-flex flex-wrap gap-3">
                <Link to="/login" className="landing-primary-button">
                  Mulai kelola zakat <span aria-hidden="true">→</span>
                </Link>
                <a href="#manfaat" className="landing-ghost-button">
                  Lihat manfaat
                </a>
              </div>
            </div>
          </Container>
          <div className="landing-hero-scroll d-none d-lg-flex align-items-center gap-2 text-white small">
            <span /> Gulir untuk melihat lebih banyak
          </div>
        </section>

        <section id="manfaat" className="landing-section landing-benefits">
          <Container>
            <div className="landing-section-heading">
              <p className="landing-eyebrow text-success">
                SATU ALUR YANG JELAS
              </p>
              <h2>Setiap rupiah punya cerita.</h2>
              <p>
                Mulai dari zakat diterima sampai bantuan sampai ke tangan yang
                membutuhkan, semuanya tercatat dan mudah dipantau.
              </p>
            </div>
            <div className="row g-4">
              {[
                [
                  "coin",
                  "Beragam sumber zakat",
                  "Fitrah, harta, perdagangan, penghasilan, dan sumber lain tercatat dalam satu tempat.",
                ],
                [
                  "safe",
                  "Dana tetap terjaga",
                  "Lihat asal dana, penggunaannya, dan sisa yang tersedia tanpa membuka banyak catatan.",
                ],
                [
                  "people",
                  "Penerima tepat sasaran",
                  "Kelola data penerima dan program bantuan dengan proses yang lebih tertib.",
                ],
              ].map(([icon, title, description]) => (
                <div className="col-md-4" key={title}>
                  <article className="landing-benefit-card h-100">
                    <div className="landing-icon">
                      <Icon name={icon as "coin" | "safe" | "people"} />
                    </div>
                    <h3>{title}</h3>
                    <p>{description}</p>
                  </article>
                </div>
              ))}
            </div>
          </Container>
        </section>

        <section id="alur" className="landing-section landing-process">
          <Container>
            <div className="row align-items-end g-4 mb-5">
              <div className="col-lg-7">
                <p className="landing-eyebrow text-success">CARA KERJA</p>
                <h2>Dibuat untuk pekerjaan nyata di lembaga zakat.</h2>
              </div>
              <div className="col-lg-5">
                <p className="mb-0 text-muted">
                  Lebih sedikit pekerjaan berulang. Lebih banyak waktu untuk
                  memastikan manfaat benar-benar sampai.
                </p>
              </div>
            </div>
            <div className="row g-4">
              {[
                [
                  "01",
                  "Catat",
                  "Simpan data muzaki, penerima, dana, dan kebutuhan bantuan.",
                ],
                [
                  "02",
                  "Periksa",
                  "Pastikan setiap keputusan dan perubahan melewati pemeriksaan yang tepat.",
                ],
                [
                  "03",
                  "Salurkan",
                  "Pantau bantuan sampai diterima dan siapkan laporan yang mudah dibaca.",
                ],
              ].map(([number, title, description]) => (
                <div className="col-md-4" key={number}>
                  <div className="landing-step">
                    <span>{number}</span>
                    <h3>{title}</h3>
                    <p>{description}</p>
                  </div>
                </div>
              ))}
            </div>
          </Container>
        </section>

        <section className="landing-cta">
          <Container className="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4">
            <div>
              <p className="landing-eyebrow text-white-50">
                MARI RAWAT KEPERCAYAAN
              </p>
              <h2 className="text-white mb-0">
                Kelola zakat dengan lebih tenang.
              </h2>
            </div>
            <Link to="/login" className="landing-light-button">
              Masuk ke ZETRA <span aria-hidden="true">→</span>
            </Link>
          </Container>
        </section>
      </main>

      <footer className="landing-footer">
        <Container className="d-flex flex-wrap justify-content-between gap-3">
          <span>© {new Date().getFullYear()} ZETRA</span>
          <span>Amanah yang tercatat. Manfaat yang terasa.</span>
        </Container>
      </footer>
    </div>
  );
};

export default LandingPage;
