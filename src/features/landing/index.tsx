import { Link } from "react-router-dom";
import { Col, Container, Nav, Navbar, Row } from "reactstrap";
import logo from "../../assets/images/zetra-logo-dark.svg";
import heroImage from "../../assets/images/landing/zakat-hero.png";

const LandingPage = () => {
  document.title = "ZETRA — Kelola Zakat dengan Amanah";

  return (
    <div className="bg-light min-vh-100">
      <Navbar
        expand="lg"
        light
        className="bg-white border-bottom py-3 sticky-top"
      >
        <Container>
          <Link to="/" className="navbar-brand d-flex align-items-center gap-2">
            <img src={logo} alt="ZETRA" height="28" />
          </Link>
          <Nav className="ms-auto align-items-center gap-2">
            <a className="nav-link d-none d-md-block" href="#cara-kerja">
              Cara kerja
            </a>
            <a className="nav-link d-none d-md-block" href="#manfaat">
              Manfaat
            </a>
            <Link to="/login" className="btn btn-primary px-4">
              Masuk
            </Link>
          </Nav>
        </Container>
      </Navbar>

      <main>
        <section className="py-5 py-lg-6 overflow-hidden">
          <Container>
            <Row className="align-items-center g-5">
              <Col lg={6}>
                <span className="badge bg-success-subtle text-success rounded-pill px-3 py-2 mb-3">
                  Zakat yang jelas, aman, dan bermanfaat
                </span>
                <h1 className="display-4 fw-bold text-dark lh-sm mb-4">
                  Menyalurkan amanah,{" "}
                  <span className="text-success">menguatkan sesama.</span>
                </h1>
                <p className="lead text-muted mb-4">
                  ZETRA membantu lembaga zakat mencatat penerimaan, mengelola
                  dana, dan menyalurkan bantuan dengan rapi—agar setiap rupiah
                  dapat dipertanggungjawabkan.
                </p>
                <div className="d-flex flex-wrap gap-3">
                  <Link to="/login" className="btn btn-success btn-lg px-4">
                    Mulai mengelola zakat{" "}
                    <i className="ri-arrow-right-line ms-1" />
                  </Link>
                  <a
                    href="#cara-kerja"
                    className="btn btn-outline-success btn-lg px-4"
                  >
                    Lihat cara kerja
                  </a>
                </div>
                <div className="d-flex flex-wrap gap-4 mt-4 text-muted small">
                  <span>
                    <i className="ri-shield-check-line text-success me-1" />
                    Data tercatat rapi
                  </span>
                  <span>
                    <i className="ri-eye-line text-success me-1" />
                    Mudah dipantau
                  </span>
                  <span>
                    <i className="ri-heart-3-line text-success me-1" />
                    Berpihak pada penerima
                  </span>
                </div>
              </Col>
              <Col lg={6}>
                <div className="rounded-4 overflow-hidden shadow-lg">
                  <img
                    src={heroImage}
                    alt="Penyaluran zakat yang amanah kepada keluarga penerima"
                    className="img-fluid w-100"
                  />
                </div>
              </Col>
            </Row>
          </Container>
        </section>

        <section id="manfaat" className="py-5 bg-white">
          <Container>
            <div className="text-center mb-5">
              <span className="text-success fw-semibold">
                Satu tempat untuk semua kebutuhan
              </span>
              <h2 className="fw-bold mt-2">
                Dari penerimaan sampai penyaluran
              </h2>
              <p className="text-muted mx-auto" style={{ maxWidth: 620 }}>
                Kelola pekerjaan harian dengan lebih sederhana, tanpa kehilangan
                ketelitian dan rasa tanggung jawab.
              </p>
            </div>
            <Row className="g-4">
              {[
                [
                  "ri-hand-coin-line",
                  "Catat penerimaan",
                  "Simpan data muzaki dan penerimaan zakat dengan rapi sejak awal.",
                ],
                [
                  "ri-safe-2-line",
                  "Jaga amanah dana",
                  "Ketahui saldo, sumber dana, dan penggunaannya dengan lebih mudah.",
                ],
                [
                  "ri-user-heart-line",
                  "Bantu yang berhak",
                  "Kelola data penerima, program bantuan, dan penyaluran dalam satu alur.",
                ],
                [
                  "ri-bar-chart-box-line",
                  "Buat laporan jelas",
                  "Lihat perkembangan dan siapkan laporan yang mudah dipahami.",
                ],
              ].map(([icon, title, description]) => (
                <Col md={6} lg={3} key={title}>
                  <div className="h-100 p-4 border rounded-4">
                    <div className="avatar-sm bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center mb-4">
                      <i className={`${icon} fs-4`} />
                    </div>
                    <h5>{title}</h5>
                    <p className="text-muted mb-0">{description}</p>
                  </div>
                </Col>
              ))}
            </Row>
          </Container>
        </section>

        <section id="cara-kerja" className="py-5">
          <Container>
            <Row className="justify-content-center text-center mb-5">
              <Col lg={7}>
                <span className="text-success fw-semibold">
                  Cara kerja yang sederhana
                </span>
                <h2 className="fw-bold mt-2">
                  Tiga langkah menuju pengelolaan yang lebih tertib
                </h2>
              </Col>
            </Row>
            <Row className="g-4">
              {[
                [
                  "01",
                  "Masukkan data",
                  "Catat muzaki, penerima, dana, dan kebutuhan program.",
                ],
                [
                  "02",
                  "Periksa dan setujui",
                  "Pastikan setiap keputusan melewati pemeriksaan yang tepat.",
                ],
                [
                  "03",
                  "Salurkan dan laporkan",
                  "Pantau bantuan sampai diterima dan buat laporan dengan percaya diri.",
                ],
              ].map(([number, title, description]) => (
                <Col md={4} key={number}>
                  <div className="d-flex gap-3">
                    <span className="display-6 fw-bold text-success">
                      {number}
                    </span>
                    <div>
                      <h5>{title}</h5>
                      <p className="text-muted">{description}</p>
                    </div>
                  </div>
                </Col>
              ))}
            </Row>
          </Container>
        </section>

        <section className="py-5 bg-success">
          <Container>
            <Row className="align-items-center g-4">
              <Col lg={8}>
                <h2 className="text-white fw-bold mb-2">
                  Mari rawat kepercayaan bersama.
                </h2>
                <p className="text-white-50 mb-0">
                  Mulai bangun pengelolaan zakat yang lebih tertib dan
                  bermanfaat hari ini.
                </p>
              </Col>
              <Col lg={4} className="text-lg-end">
                <Link to="/login" className="btn btn-light btn-lg px-4">
                  Masuk ke ZETRA <i className="ri-arrow-right-line ms-1" />
                </Link>
              </Col>
            </Row>
          </Container>
        </section>
      </main>

      <footer className="bg-white py-4 border-top">
        <Container className="d-flex flex-wrap justify-content-between gap-2">
          <span className="text-muted small">
            © {new Date().getFullYear()} ZETRA
          </span>
          <span className="text-muted small">
            Pengelolaan zakat yang amanah dan transparan
          </span>
        </Container>
      </footer>
    </div>
  );
};

export default LandingPage;
