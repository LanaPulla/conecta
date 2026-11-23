</div>
<footer class="bg-dark text-white text-center py-3 mt-5">
    <small>Conecta &copy; 2026 - Trabalho acadêmico original. | <a href="termos.php" class="text-info">Termos de Uso</a></small>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/validacao.js"></script>
<?php if (isset($_SESSION['usuario_id'])): ?>
    <script src="js/avisos.js"></script>
<?php endif; ?>
</body>

</html>
