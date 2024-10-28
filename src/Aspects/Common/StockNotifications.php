<?php
namespace Mewz\WCAS\Aspects\Common;

use Mewz\Framework\Base\Aspect;
use Mewz\Framework\Util\Number;
use Mewz\WCAS\Models\AttributeStock;

class StockNotifications extends Aspect
{
	public function __hooks()
	{
		add_action('mewz_wcas_trigger_stock_notification', [$this, 'trigger_stock_notification'], 10, 2);
		add_action('mewz_wcas_trigger_no_stock_notification', [$this, 'send_no_stock_email']);
		add_action('mewz_wcas_trigger_low_stock_notification', [$this, 'send_low_stock_email']);
	}

	public function trigger_stock_notification(AttributeStock $stock, $prev_quantity)
	{
		if ($stock->context() !== 'view') {
			// get a new stock instance with 'view' context
			$stock = AttributeStock::instance($stock);
		}

		// get the exact quantity to account for rounding in view context
		$quantity = $stock->get_contextual('quantity', 'edit');
		$low_stock = $stock->low_stock();

		if ($quantity > $low_stock) return;

		$no_stock = (float)get_option('woocommerce_notify_no_stock_amount', 0);

		if ($low_stock < $no_stock) return;

		if ($quantity <= $no_stock && $prev_quantity > $no_stock) {
			if (wc_string_to_bool(get_option('woocommerce_notify_no_stock', 'yes'))) {
				do_action('mewz_wcas_trigger_no_stock_notification', $stock);
			}
		} elseif ($quantity <= $low_stock && $prev_quantity > $low_stock) {
			if (wc_string_to_bool(get_option('woocommerce_notify_low_stock', 'yes'))) {
				do_action('mewz_wcas_trigger_low_stock_notification', $stock);
			}
		}
	}

	public function send_no_stock_email(AttributeStock $stock)
	{
		$args = [
			'recipient' => get_option('woocommerce_stock_email_recipient'),
			'subject' => $this->get_subject_prefix() . sprintf(__('Out of stock - %s', 'woocommerce-attribute-stock'), $this->get_stock_title($stock)),
			'message' => $this->wrap_message(sprintf(__('%s is out of stock.', 'woocommerce-attribute-stock'), $this->get_stock_title($stock, true))),
			'headers' => ['Content-type: text/html; charset: utf8'],
			'attachments' => [],
		];

		$args = apply_filters('mewz_wcas_no_stock_email_args', $args, $stock);

		return wp_mail($args['recipient'], $args['subject'], $args['message'], $args['headers'], $args['attachments']);
	}

	public function send_low_stock_email(AttributeStock $stock)
	{
		$quantity = $stock->quantity();

		$args = [
			'recipient' => get_option('woocommerce_stock_email_recipient'),
			'subject' => $this->get_subject_prefix() . sprintf(__('Low in stock - %s', 'woocommerce-attribute-stock'), $this->get_stock_title($stock)),
			'message' => $this->wrap_message(sprintf(_n('%s is low in stock. There is %f left.', '%s is low in stock. There are %f left.', $quantity, 'woocommerce-attribute-stock'), $this->get_stock_title($stock, true), Number::local_format($quantity))),
			'headers' => ['Content-type: text/html; charset: utf8'],
			'attachments' => [],
		];

		$args = apply_filters('mewz_wcas_low_stock_email_args', $args, $stock);

		return wp_mail($args['recipient'], $args['subject'], $args['message'], $args['headers'], $args['attachments']);
	}

	public function get_subject_prefix()
	{
		static $prefix;
		return $prefix ??= '[' . wp_specialchars_decode(get_option('blogname'), ENT_QUOTES) . '] ';
	}

	public function get_stock_title(AttributeStock $stock, $link = false)
	{
		$title = esc_html($stock->title());

		if ($link) {
			$title = '<a href="' . esc_url($stock->edit_url()) . '">' . $title . '</a>';
		}

		if (($sku = trim($stock->sku())) !== '') {
			$title .= ' (' . esc_html($sku) . ')';
		}

		return $title;
	}

	public function wrap_message($message)
	{
		return '<p style="font-family: \'Helvetica Neue\', \'Open Sans\', Arial, sans-serif;">' . $message . '</p>';
	}
}
