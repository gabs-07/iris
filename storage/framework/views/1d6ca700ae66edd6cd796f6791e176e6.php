<?php if(session('success')): ?>
  <div class="backend-alert success"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('warning')): ?>
  <div class="backend-alert warning"><?php echo e(session('warning')); ?></div>
<?php endif; ?>
<?php if(session('message')): ?>
  <div class="backend-alert success"><?php echo e(session('message')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
  <div class="backend-alert danger"><ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div>
<?php endif; ?>
<?php /**PATH C:\laragon\www\iris-escom\resources\views/partials/frontend-alerts.blade.php ENDPATH**/ ?>