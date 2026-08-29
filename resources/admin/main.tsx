import { createRoot } from "react-dom/client"

import App from "./App"
import "./styles/main.css"
import "./wp-admin"

const rootElement = document.getElementById("picowind-app")

if (rootElement) {
  createRoot(rootElement).render(<App />)
}
