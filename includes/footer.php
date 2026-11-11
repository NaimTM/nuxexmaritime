<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <div class="logo logo-light">
        <span class="logo-mark">⚓</span>
        <span class="logo-text">NUZEX<small>MARITIME</small></span>
      </div>
      <p class="muted">Marine manning & crew management agency based in Dhaka, Bangladesh — serving shipowners and seafarers worldwide.</p>
    </div>

    <div>
      <h4>Company</h4>
      <ul>
        <li><a href="/about.php">About Us</a></li>
        <li><a href="/services.php">Services</a></li>
        <li><a href="/compliance.php">Compliance</a></li>
        <li><a href="/contact.php">Contact</a></li>
      </ul>
    </div>

    <div>
      <h4>For Shipowners</h4>
      <ul>
        <li><a href="/for-shipowners.php">Overview</a></li>
        <li><a href="/request-crew.php">Request Crew</a></li>
        <li><a href="/faq.php">FAQ</a></li>
      </ul>
    </div>

    <div>
      <h4>For Seafarers</h4>
      <ul>
        <li><a href="/register.php">Register</a></li>
        <li><a href="/jobs.php">Live Jobs</a></li>
        <li><a href="/for-seafarers.php">How It Works</a></li>
      </ul>
    </div>
  </div>

  <div class="container footer-bottom">
    <p>© <?= date('Y') ?> Nuzex Maritime. All rights reserved.</p>
    <div class="legal-links">
      <a href="/privacy-policy.php">Privacy</a>
      <a href="/terms.php">Terms</a>
      <a href="/cookies.php">Cookies</a>
    </div>
  </div>
</footer>

<a href="https://wa.me/<?= preg_replace('/\D/','',WHATSAPP) ?>" class="whatsapp-float" target="_blank" rel="noopener" aria-label="WhatsApp">
  <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor"><path d="M20.5 3.5A11.8 11.8 0 0 0 12 0 12 12 0 0 0 1.6 17.9L0 24l6.3-1.6A12 12 0 0 0 12 24a12 12 0 0 0 8.5-20.5zM12 22a10 10 0 0 1-5.1-1.4l-.4-.2-3.7.9 1-3.6-.3-.4A10 10 0 1 1 12 22zm5.6-7.5c-.3-.2-1.8-.9-2.1-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7 0a8.2 8.2 0 0 1-4-3.5c-.3-.5.3-.5.8-1.6a.6.6 0 0 0 0-.5l-.9-2.1c-.2-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.9.4 3.6 3.6 0 0 0-1.1 2.6 6.2 6.2 0 0 0 1.3 3.3 14 14 0 0 0 5.4 4.7c2.5 1 3 .8 3.5.7a3.2 3.2 0 0 0 2.2-1.5 2.7 2.7 0 0 0 .2-1.5c-.1-.2-.3-.3-.6-.5z"/></svg>
</a>

<script src="/assets/js/main.js" defer></script>
</body>
</html>