    </div>

</main>

<footer class="app-footer">

    <div class="container-fluid px-4">

        <div class="row align-items-center">

            <div class="col-md-6 text-center text-md-start">

                <span>
                    <?= e(nombreAplicacion()) ?>
                </span>

            </div>

            <div class="col-md-6 text-center text-md-end">

                <span>
                    Versión <?= e(versionAplicacion()) ?>
                </span>

                <span class="mx-2">|</span>

                <span>
                    <?= date('Y') ?>
                </span>

            </div>

        </div>

    </div>

</footer>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const alerts = document.querySelectorAll(
        '.alert[data-auto-dismiss="true"]'
    );

    alerts.forEach(function (alert) {

        setTimeout(function () {

            const bsAlert =
                bootstrap.Alert.getOrCreateInstance(alert);

            bsAlert.close();

        }, 5000);

    });

});

</script>

</body>
</html>