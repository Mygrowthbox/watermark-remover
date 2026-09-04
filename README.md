# watermark-remover
Open-source dual-pass architecture designed to reduce statistical AI watermarks and restructure generated text. Features factual data protection (URLs, dates, numbers) and a real-time diff viewer.

This repository contains the technical demonstration source code for the dual-pass algorithm and data preservation pipeline designed for text restructuring and statistical watermark reduction.

 🚨 **Usage Notice:**
> This repository is published **strictly for demonstration and technical auditing purposes**. 
> If you want to use the tool directly without setting up servers or API keys, access the official web interface: https://mygrowthbox.com/en/watermark-remover/

---

🎯 Overview & Technical Architecture

This tool was designed to alter the statistical signatures of AI-generated text while preserving factual integrity and formatting:

1. **Pass 1 - Unstructuring:** Breaks predictable syntax patterns and n-gram perplexity based on the selected adjustment level (*Discrete*, *Balanced*, *Deep*).
2. **Pass 2 - Smoothing & Protection:** Harmonizes style while isolating and safeguarding critical entities (URLs, dates, proper nouns, numerical figures, and Markdown markup).
3. **Frontend UI:** Built with vanilla JavaScript featuring a real-time word counter, dynamic multi-language toggle, and an inline Diff-Viewer (red/green highlight).

---

💡 Model Transparency & Limitations

* **Detector Efficacy:** No tool can guarantee a 0% AI detection score on all third-party detectors, as detection algorithms and statistical thresholds evolve continuously.
* **API Models:** Open-source LLMs deployed via Groq and OpenRouter currently do not contain known native watermarking algorithms, though provider policies and model weights may evolve over time.

---

📄 License & Terms

All Rights Reserved. Access to this source code is provided solely for educational review and technical inspection. Redistribution, standalone installation on third-party WordPress sites, or commercial exploitation without prior written consent is strictly prohibited (see the [`LICENSE`](./LICENSE) file).
