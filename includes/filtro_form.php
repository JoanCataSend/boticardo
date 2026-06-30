<form action="catalogo.php" method="GET" class="filter-form" id="catalog-filter-form">
    <div class="filter-header">
        <h2>Filtros</h2>
        <a href="catalogo.php" class="filter-reset">Limpiar</a>
    </div>

    <!-- Categorías -->
    <div class="filter-group">
        <h3 class="filter-group__title">Categorías</h3>
        <?php foreach ($categoriasInfo as $id => $info): ?>
            <label class="filter-checkbox">
                <input type="radio" name="categoria" value="<?= $id ?>" <?= (isset($categoria_id) && $categoria_id === $id) ? 'checked' : '' ?>>
                <?= e($info['nombre']) ?>
            </label>
        <?php endforeach; ?>
    </div>

    <!-- Precio -->
    <div class="filter-group">
        <h3 class="filter-group__title">Precio</h3>
        <div class="filter-price-range">
            <div class="price-input">
                <label for="min-price">Min</label>
                <input type="number" id="min-price" name="min_price" placeholder="0" value="<?= e((string)($min_price ?? '')) ?>">
            </div>
            <span>-</span>
            <div class="price-input">
                <label for="max-price">Max</label>
                <input type="number" id="max-price" name="max_price" placeholder="100" value="<?= e((string)($max_price ?? '')) ?>">
            </div>
        </div>
    </div>

    <!-- Marcas -->
    <div class="filter-group">
        <h3 class="filter-group__title">Marca</h3>
        <label class="filter-checkbox">
            <input type="radio" name="marca" value="" <?= empty($marca) ? 'checked' : '' ?>>
            Todas las marcas
        </label>
        <?php if (!empty($marcasDisponibles)): ?>
            <?php foreach ($marcasDisponibles as $marcaDisponible): ?>
                <?php
                $nombreMarca = (string) ($marcaDisponible['nombre'] ?? '');
                $totalMarca = (int) ($marcaDisponible['total_productos'] ?? 0);
                ?>
                <?php if ($nombreMarca !== ''): ?>
                    <label class="filter-checkbox">
                        <input type="radio" name="marca" value="<?= e($nombreMarca) ?>" <?= (isset($marca) && $marca === $nombreMarca) ? 'checked' : '' ?>>
                        <?= e($nombreMarca) ?>
                        <span class="filter-count">(<?= $totalMarca ?>)</span>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="filter-empty">No hay marcas disponibles.</p>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 1rem;">Aplicar</button>
</form>
